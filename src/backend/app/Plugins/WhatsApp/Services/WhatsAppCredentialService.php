<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Exceptions\WhatsAppApiException;
use App\Plugins\WhatsApp\Models\WhatsAppCredential;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class WhatsAppCredentialService {
    public const SECRET_FIELDS = ['api_access_token', 'webhook_verify_token', 'meta_app_secret'];

    public function __construct(private WhatsAppCredential $credential) {}

    public function createCredentials(): WhatsAppCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'phone_number_id' => null,
            'whatsapp_business_account_id' => null,
            'api_access_token' => null,
            'sender_phone_number' => null,
            'webhook_verify_token' => null,
            'meta_app_secret' => null,
            'api_version' => 'v21.0',
            'is_enabled' => false,
        ]);
    }

    public function getCredentials(): ?WhatsAppCredential {
        return $this->decryptCredentials($this->credential->newQuery()->first() ?? $this->createCredentials());
    }

    /**
     * @return array{configured: bool, connected: bool, missing_fields: string[], sender: string}
     */
    public function getConnectionStatus(): array {
        $credential = $this->getCredentials() ?? $this->createCredentials();

        $requiredFields = ['phone_number_id', 'whatsapp_business_account_id', 'api_access_token', 'sender_phone_number', 'api_version'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            $value = $credential->{$field};

            if (!is_string($value) || trim($value) === '') {
                $missingFields[] = $field;
            }
        }

        $configured = $missingFields === [];

        return [
            'configured' => $configured,
            'connected' => $configured && (bool) $credential->is_enabled,
            'missing_fields' => $missingFields,
            'sender' => $credential->sender_phone_number ?: '',
        ];
    }

    /** @param array<string, mixed> $data */
    public function updateCredentials(#[\SensitiveParameter] array $data): WhatsAppCredential {
        foreach (self::SECRET_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if (!self::isValidSecret($data[$field])) {
                throw ValidationException::withMessages([$field => 'Provide a replacement secret or explicit null; omit unchanged secrets.']);
            }
            if ($data[$field] !== null) {
                try {
                    $data[$field] = Crypt::encryptString($data[$field]);
                } catch (\Throwable) {
                    throw new WhatsAppApiException('Unable to securely store WhatsApp credentials.');
                }
            }
        }

        return $this->credential->getConnection()->transaction(function () use ($data): WhatsAppCredential {
            $credential = $this->credential->newQuery()->firstOrCreate(['id' => $data['id'] ?? 1]);
            unset($data['id']);
            $credential->fill($data);
            $decrypted = $this->decryptCredentials(clone $credential);
            $credential->save();

            // Keep plaintext out of the model being persisted.
            $decrypted->setAttribute('created_at', $credential->created_at);
            $decrypted->setAttribute('updated_at', $credential->updated_at);

            return $decrypted;
        });
    }

    public static function isValidSecret(#[\SensitiveParameter] mixed $value): bool {
        return $value === null || (is_string($value) && trim($value) !== '' && !preg_match('/^[*•]+$/u', trim($value)));
    }

    private function decryptCredentials(#[\SensitiveParameter] WhatsAppCredential $credential): WhatsAppCredential {
        foreach (self::SECRET_FIELDS as $field) {
            $value = $credential->getAttribute($field);
            if ($value === null) {
                $credential->setAttribute($field, null);
                continue;
            }
            try {
                $credential->setAttribute($field, Crypt::decryptString($value));
            } catch (\Throwable) {
                throw new WhatsAppApiException('Unable to securely read WhatsApp credentials.');
            }
        }

        // Decrypted return values must not be dirty writes if a caller saves another field.
        $credential->syncOriginalAttributes(self::SECRET_FIELDS);

        return $credential;
    }
}
