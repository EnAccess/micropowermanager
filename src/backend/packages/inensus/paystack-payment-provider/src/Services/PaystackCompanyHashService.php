<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PaystackCompanyHashService {
    private const HASH_LENGTH = 16;

    public function generateHash(int $companyId): string {
        $salt = $this->getHashSalt();
        $data = $companyId . '|' . $salt . '|' . time();
        
        // Generate a URL-safe hash
        $hash = base64_encode(hash('sha256', $data, true));
        $hash = str_replace(['+', '/', '='], ['-', '_', ''], $hash);
        
        // Truncate to desired length
        return substr($hash, 0, self::HASH_LENGTH);
    }

    public function generatePermanentHash(int $companyId): string {
        $salt = $this->getHashSalt();
        $data = $companyId . '|' . $salt . '|permanent';
        
        // Generate a URL-safe hash
        $hash = base64_encode(hash('sha256', $data, true));
        $hash = str_replace(['+', '/', '='], ['-', '_', ''], $hash);
        
        // Truncate to desired length
        return substr($hash, 0, self::HASH_LENGTH);
    }

    public function validateHash(string $hash, int $companyId): bool {
        if (strlen($hash) !== self::HASH_LENGTH) {
            return false;
        }

        // First check if it's a permanent hash
        if ($this->validatePermanentHash($hash, $companyId)) {
            return true;
        }

        // Then check if it's a time-based hash
        return $this->validateTimeBasedHash($hash, $companyId);
    }

    public function validatePermanentHash(string $hash, int $companyId): bool {
        $expectedHash = $this->generatePermanentHash($companyId);
        return hash_equals($hash, $expectedHash);
    }

    public function validateTimeBasedHash(string $hash, int $companyId): bool {
        $salt = $this->getHashSalt();
        
        // For validation, we need to check against recent timestamps
        // Allow hash to be valid for 24 hours
        $currentTime = time();
        for ($i = 0; $i < 86400; $i += 3600) { // Check every hour for 24 hours
            $testData = $companyId . '|' . $salt . '|' . ($currentTime - $i);
            $testHash = base64_encode(hash('sha256', $testData, true));
            $testHash = str_replace(['+', '/', '='], ['-', '_', ''], $testHash);
            $testHash = substr($testHash, 0, self::HASH_LENGTH);
            
            if (hash_equals($hash, $testHash)) {
                return true;
            }
        }

        return false;
    }

    public function generatePublicUrl(int $companyId, string $type = 'payment'): string {
        $hash = $this->generateHash($companyId);
        
        return "/paystack/public/{$type}/{$hash}/{$companyId}";
    }

    public function generatePermanentPaymentUrl(int $companyId): string {
        $hash = $this->generatePermanentHash($companyId);
        
        return "/paystack/public/payment/{$hash}/{$companyId}";
    }

    public function generateAgentPaymentUrl(int $companyId, ?int $customerId = null, ?int $agentId = null): string {
        $hash = $this->generateHash($companyId); // Time-based hash
        $url = "/paystack/public/payment/{$hash}/{$companyId}";
        
        $params = [];
        if ($customerId) {
            $params['customer'] = $customerId;
        }
        if ($agentId) {
            $params['agent'] = $agentId;
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }



    private function getHashSalt(): string {
        $salt = config('paystack-payment-provider.company_hash_salt');
        
        if (empty($salt)) {
            // Fallback to app key if no specific salt is configured
            $salt = config('app.key');
        }
        
        return $salt;
    }

    public function isUrlSafe(string $hash): bool {
        // Check if hash contains only URL-safe characters
        return preg_match('/^[a-zA-Z0-9_-]+$/', $hash) === 1;
    }
}
