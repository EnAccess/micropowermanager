<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Services;

use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomCredential;
use App\Traits\EncryptsCredentials;

class SafaricomCredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['consumer_key', 'consumer_secret', 'passkey'];

    public function __construct(
        private SafaricomCredential $credential,
        private SafaricomAuthService $authService,
    ) {}

    public function getCredentials(): SafaricomCredential {
        $credential = $this->credential->newQuery()->first();
        if (!$credential) {
            return $this->createCredentials();
        }

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    public function createCredentials(): SafaricomCredential {
        return $this->credential->newQuery()->create([
            'consumer_key' => '',
            'consumer_secret' => '',
            'passkey' => '',
            'shortcode' => '',
            'environment' => 'sandbox',
            'validation_url' => null,
            'confirmation_url' => null,
            'timeout_url' => null,
            'result_url' => null,
        ]);
    }

    public function hasCredentials(): bool {
        return $this->credential->newQuery()->exists();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): SafaricomCredential {
        $credential = $this->getCredentials();
        $secretsRotated = array_key_exists('consumer_key', $data)
            || array_key_exists('consumer_secret', $data)
            || array_key_exists('passkey', $data);
        $environmentChanged = array_key_exists('environment', $data)
            && $data['environment'] !== $credential->environment;

        $credential->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));
        $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);

        if ($secretsRotated || $environmentChanged) {
            $this->authService->clearAccessToken();
        }

        return $credential;
    }
}
