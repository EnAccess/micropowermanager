<?php

declare(strict_types=1);

namespace App\Services;

use Hashids\Hashids;

/**
 * Shared URL-hashing logic for payment-provider plugins that expose tenanted,
 * public payment links. Subclasses only supply the URL path prefix and the
 * config key that holds the per-plugin hash salt.
 *
 * Two hash schemes:
 * - permanent: stable per-company; used for the dashboard's "share this URL"
 *   workflow.
 * - time-based: includes the current timestamp and only validates within a
 *   24h sliding window; used for short-lived agent links.
 *
 * Company IDs in URLs are obfuscated via Hashids so a public link never leaks
 * a raw integer ID.
 */
abstract class AbstractPaymentProviderCompanyHashService {
    private const HASH_LENGTH = 16;
    private const TIME_BASED_WINDOW_SECONDS = 86400;
    private const TIME_BASED_BUCKET_SECONDS = 3600;

    abstract protected function getUrlPrefix(): string;

    abstract protected function getSaltConfigKey(): string;

    public function generateHash(int $companyId): string {
        return $this->buildHash($companyId.'|'.$this->getHashSalt().'|'.time());
    }

    public function generatePermanentHash(int $companyId): string {
        return $this->buildHash($companyId.'|'.$this->getHashSalt().'|permanent');
    }

    public function validateHash(string $hash, int $companyId): bool {
        if (strlen($hash) !== self::HASH_LENGTH) {
            return false;
        }

        if ($this->validatePermanentHash($hash, $companyId)) {
            return true;
        }

        return $this->validateTimeBasedHash($hash, $companyId);
    }

    public function validatePermanentHash(string $hash, int $companyId): bool {
        return hash_equals($hash, $this->generatePermanentHash($companyId));
    }

    public function validateTimeBasedHash(string $hash, int $companyId): bool {
        $salt = $this->getHashSalt();
        $currentTime = time();
        for ($offset = 0; $offset < self::TIME_BASED_WINDOW_SECONDS; $offset += self::TIME_BASED_BUCKET_SECONDS) {
            $candidate = $this->buildHash($companyId.'|'.$salt.'|'.($currentTime - $offset));
            if (hash_equals($hash, $candidate)) {
                return true;
            }
        }

        return false;
    }

    public function generatePublicUrl(int $companyId, string $type = 'payment'): string {
        $hash = $this->generateHash($companyId);
        $token = $this->generateHashFromCompanyId($companyId);

        return $this->getUrlPrefix()."/public/{$type}/{$hash}?ct=".$token;
    }

    public function generatePermanentPaymentUrl(int $companyId): string {
        $hash = $this->generatePermanentHash($companyId);
        $token = $this->generateHashFromCompanyId($companyId);

        return $this->getUrlPrefix()."/public/payment/{$hash}?ct=".$token;
    }

    public function generateAgentPaymentUrl(int $companyId, ?int $customerId = null, ?int $agentId = null): string {
        $url = $this->generatePublicUrl($companyId, 'payment');

        $params = [];
        if ($customerId) {
            $params['customer'] = $customerId;
        }
        if ($agentId) {
            $params['agent'] = $agentId;
        }

        if ($params !== []) {
            // ct=... is already in the query string, so any extra params need to
            // join with '&', not '?'. The earlier per-plugin implementations
            // used '?' here, which produced an invalid URL with two query
            // strings.
            $url .= '&'.http_build_query($params);
        }

        return $url;
    }

    public function isUrlSafe(string $hash): bool {
        return preg_match('/^[a-zA-Z0-9_-]+$/', $hash) === 1;
    }

    public function generateHashFromCompanyId(int $companyId): string {
        return $this->getHashids()->encode($companyId);
    }

    public function parseHashFromCompanyId(string $hash): ?int {
        try {
            $decoded = $this->getHashids()->decode($hash);
            if (count($decoded) === 0) {
                return null;
            }
            $companyId = $decoded[0];

            return $companyId > 0 ? $companyId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildHash(string $data): string {
        $hash = base64_encode(hash('sha256', $data, true));
        $hash = str_replace(['+', '/', '='], ['-', '_', ''], $hash);

        return substr($hash, 0, self::HASH_LENGTH);
    }

    private function getHashSalt(): string {
        $salt = config($this->getSaltConfigKey());
        if (empty($salt)) {
            $salt = config('app.key');
        }

        return $salt;
    }

    private function getHashids(): Hashids {
        return new Hashids($this->getHashSalt(), 8);
    }
}
