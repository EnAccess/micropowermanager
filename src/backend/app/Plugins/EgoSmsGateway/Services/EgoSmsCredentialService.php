<?php

namespace App\Plugins\EgoSmsGateway\Services;

use App\Plugins\EgoSmsGateway\Models\EgoSmsCredential;
use App\Traits\EncryptsCredentials;

class EgoSmsCredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['password'];

    public function __construct(
        private EgoSmsCredential $credential,
    ) {}

    public function createCredentials(): EgoSmsCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'username' => null,
            'password' => null,
            'sender_id' => null,
        ]);
    }

    public function getCredentials(): ?EgoSmsCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * @param array{username: string, password: string, sender_id: string} $data
     */
    public function updateCredentials(array $data): EgoSmsCredential {
        $credential = $this->createCredentials();
        $credential->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));

        return $this->decryptCredentialFields($credential->fresh(), self::ENCRYPTED_FIELDS);
    }
}
