<?php

namespace App\Plugins\AirtelSmsGateway\Services;

use App\Plugins\AirtelSmsGateway\Models\AirtelSmsCredential;
use App\Traits\EncryptsCredentials;

class AirtelSmsCredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['password'];

    public function __construct(
        private AirtelSmsCredential $credential,
    ) {}

    public function createCredentials(): AirtelSmsCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1]);
    }

    public function getCredentials(): ?AirtelSmsCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * @param array{host: string, port: int, system_id: string, password: string, source_address: string} $data
     */
    public function updateCredentials(array $data): AirtelSmsCredential {
        $credential = $this->createCredentials();
        $credential->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::ENCRYPTED_FIELDS);
    }
}
