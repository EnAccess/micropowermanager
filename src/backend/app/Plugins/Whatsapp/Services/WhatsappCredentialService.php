<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WhatsappCredential;
use App\Traits\EncryptsCredentials;

class WhatsappCredentialService {
    use EncryptsCredentials;

    public function __construct(private WhatsappCredential $credential) {}

    public function createCredentials(): WhatsappCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1]);
    }

    public function getCredentials(): ?WhatsappCredential {
        $credential = $this->credential->newQuery()->first();

        return $credential ? $this->decryptCredentialFields($credential, ['access_token']) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): WhatsappCredential {
        $credential = $this->credential->newQuery()->findOrFail($data['id'] ?? 1);

        // keep the saved token when the form leaves it blank
        if (empty($data['access_token'])) {
            unset($data['access_token']);
        } else {
            $data = $this->encryptCredentialFields($data, ['access_token']);
        }

        $credential->update($data);

        return $this->decryptCredentialFields($credential->fresh(), ['access_token']);
    }
}
