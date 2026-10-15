<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Services;

use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Traits\EncryptsCredentials;

/**
 * Manages storage and encryption of WhatsApp Business API credentials.
 */
class WhatsappCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private WhatsappCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): WhatsappCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'access_token' => null,
            'phone_number_id' => null,
            'waba_id' => null,
            'webhook_secret' => null,
            'app_secret' => null,
            'transaction_confirmation_template' => null,
            'reminder_template' => null,
        ]);
    }

    public function getCredentials(): ?WhatsappCredential {
        $credential = $this->credential->newQuery()->first();

        if (!$credential) {
            return null;
        }

        return $this->decryptCredentialFields($credential, [
            'access_token',
            'phone_number_id',
            'waba_id',
            'webhook_secret',
            'app_secret',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): WhatsappCredential {
        $credential = $this->credential->newQuery()->firstOrCreate(['id' => 1]);

        $encryptedData = $this->encryptCredentialFields($data, [
            'access_token',
            'phone_number_id',
            'waba_id',
            'webhook_secret',
            'app_secret',
        ]);

        $credential->update($encryptedData);
        $credential->fresh();

        return $this->decryptCredentialFields($credential, [
            'access_token',
            'phone_number_id',
            'waba_id',
            'webhook_secret',
            'app_secret',
        ]);
    }
}
