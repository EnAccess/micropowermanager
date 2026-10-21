<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WhatsappCredential;
use App\Traits\EncryptsCredentials;

class WhatsappCredentialService {
    use EncryptsCredentials;

    private const array SECRET_FIELDS = ['access_token', 'app_secret'];

    public function __construct(private WhatsappCredential $credential) {}

    public function createCredentials(): WhatsappCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1]);
    }

    public function getCredentials(): ?WhatsappCredential {
        $credential = $this->credential->newQuery()->first();

        return $credential ? $this->decryptCredentialFields($credential, self::SECRET_FIELDS) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): WhatsappCredential {
        $credential = $this->credential->newQuery()->findOrFail($data['id'] ?? 1);

        // a blank secret keeps the saved one
        foreach (self::SECRET_FIELDS as $field) {
            if (empty($data[$field])) {
                unset($data[$field]);
            }
        }

        $credential->update($this->encryptCredentialFields($data, self::SECRET_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::SECRET_FIELDS);
    }
}
