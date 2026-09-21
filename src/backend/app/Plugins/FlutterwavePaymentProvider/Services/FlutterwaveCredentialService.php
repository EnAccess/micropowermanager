<?php

namespace App\Plugins\FlutterwavePaymentProvider\Services;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveCredential;
use Illuminate\Support\Facades\Crypt;

class FlutterwaveCredentialService {
    public function __construct(
        private FlutterwaveCredential $flutterwaveCredential,
    ) {}

    public function getCredentials(): FlutterwaveCredential {
        $credential = $this->flutterwaveCredential->newQuery()->first();

        if (!$credential) {
            return $this->createCredentials();
        }

        return $credential;
    }

    public function createCredentials(): FlutterwaveCredential {
        return $this->flutterwaveCredential->newQuery()->create([
            'secret_key' => '',
            'public_key' => '',
            'encryption_key' => '',
            'webhook_secret_hash' => '',
            'callback_url' => '',
            'merchant_name' => 'Flutterwave',
            'merchant_email' => null,
            'environment' => 'test',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): FlutterwaveCredential {
        $credential = $this->getCredentials();
        if (array_key_exists('secret_key', $data)) {
            $data['secret_key'] = Crypt::encrypt($data['secret_key']);
        }
        if (array_key_exists('public_key', $data)) {
            $data['public_key'] = Crypt::encrypt($data['public_key']);
        }
        if (array_key_exists('encryption_key', $data)) {
            $data['encryption_key'] = Crypt::encrypt($data['encryption_key']);
        }
        if (array_key_exists('webhook_secret_hash', $data)) {
            $data['webhook_secret_hash'] = Crypt::encrypt($data['webhook_secret_hash']);
        }
        $credential->update($data);

        if ($credential->getSecretKey() === '' || $credential->getPublicKey() === '' || $credential->getEncryptionKey() === '' || $credential->getWebhookSecretHash() === '') {
            throw new \RuntimeException('Secret key, public key, encryption key and webhook secret hash are required.');
        }

        return $credential;
    }

    public function hasCredentials(): bool {
        return $this->flutterwaveCredential->newQuery()->exists();
    }
}
