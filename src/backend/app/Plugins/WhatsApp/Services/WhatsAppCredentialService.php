<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use App\Traits\EncryptsCredentials;

class WhatsAppCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private WhatsAppCredential $credential,
    ) {}

    /**
     * Creates default empty credentials if none exist
     */
    public function createCredentials(): WhatsAppCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'phone_number_id' => null,
            'whatsapp_business_account_id' => null,
            'api_access_token' => null,
            'sender_phone_number' => null,
            'webhook_verify_token' => null,
            'api_version' => 'v21.0',
            'is_enabled' => false
        ]);
    }

    /**
     * Retrieves credentials and decrypts sensitive tokens,
     */
    public function getCredentials(): ?WhatsAppCredential {
        $credential = $this->credential->newQuery()->first();

        if(!$credential) {
            $credential = $this->createCredentials();
        }

        return $this->decryptCredentialFields($credential, ['api_access_token', 'webhook_verify_token']);
    }

    /**
     * Updates credentials and encrypts sensitive tokens before saving
     * 
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): WhatsAppCredential {
        $credential = $this->credential->newQuery()->firstOrCreate(['id' => $data['id'] ?? 1]);

        $encryptedData = $this->encryptCredentialFields($data, ['api_access_token', 'webhook_verify_token']);

        $credential->update($encryptedData);
        $credential->save();
        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['api_access_token', 'webhook_verify_token']);
    }
}
