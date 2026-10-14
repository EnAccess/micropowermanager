<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use App\Traits\EncryptsCredentials;

class WhatsAppCredentialService {
    use EncryptsCredentials;

    /** @var string[] */
    private const array SECRET_FIELDS = ['api_access_token', 'webhook_verify_token'];

    public function __construct(
        private WhatsAppCredential $credential,
    ) {}

    /**
     * Creates default empty credentials if none exist.
     */
    public function createCredentials(): WhatsAppCredential {
        return $this->findOrCreate();
    }

    /**
     * Retrieves credentials and decrypts sensitive tokens.
     */
    public function getCredentials(): ?WhatsAppCredential {
        return $this->decryptCredentialFields($this->findOrCreate(), self::SECRET_FIELDS);
    }

    /**
     * A single credential row backs the whole tenant, so every accessor must
     * operate on the same record. Forcing an id would silently create a second
     * row and leave the first one in place.
     */
    private function findOrCreate(): WhatsAppCredential {
        return $this->credential->newQuery()->first() ?? $this->credential->newQuery()->create([
            'api_version' => 'v21.0',
            'is_enabled' => false,
        ]);
    }

    /**
     * Updates credentials and encrypts sensitive tokens before saving.
     *
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): WhatsAppCredential {
        $credential = $this->findOrCreate();

        // An empty secret means the operator left the masked field untouched,
        // so the stored value must be preserved rather than cleared.
        foreach (self::SECRET_FIELDS as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                unset($data[$field]);
            }
        }

        $encryptedData = $this->encryptCredentialFields($data, self::SECRET_FIELDS);

        $credential->update($encryptedData);
        $credential->save();

        return $this->decryptCredentialFields($credential->fresh(), self::SECRET_FIELDS);
    }

    /**
     * @return array{configured: bool, connected: bool, missing_fields: string[], sender: string}
     */
    public function getConnectionStatus(): array {
        $credential = $this->getCredentials();

        $missingFields = [];
        foreach (['api_access_token', 'phone_number_id', 'whatsapp_business_account_id'] as $field) {
            if (empty($credential->{$field})) {
                $missingFields[] = $field;
            }
        }

        return [
            'configured' => $missingFields === [],
            'connected' => $missingFields === [] && (bool) $credential->is_enabled,
            'missing_fields' => $missingFields,
            'sender' => (string) ($credential->sender_phone_number ?? ''),
        ];
    }
}
