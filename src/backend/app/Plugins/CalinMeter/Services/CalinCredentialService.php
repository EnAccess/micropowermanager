<?php

namespace App\Plugins\CalinMeter\Services;

use App\Plugins\CalinMeter\Models\CalinCredential;
use App\Traits\EncryptsCredentials;

class CalinCredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['user_id', 'api_key'];

    public function __construct(
        private CalinCredential $credential,
    ) {}

    public function createCredentials(): CalinCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_id' => null,
            'api_key' => null,
        ]);
    }

    public function getCredentials(): ?CalinCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * @param array{api_url?: string, user_id?: string, api_key?: string} $data
     */
    public function updateCredentials(array $data): CalinCredential {
        $credential = $this->credential->newQuery()->first() ?? $this->createCredentials();

        $credential->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::ENCRYPTED_FIELDS);
    }
}
