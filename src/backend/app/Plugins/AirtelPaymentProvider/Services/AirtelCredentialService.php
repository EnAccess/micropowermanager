<?php

namespace App\Plugins\AirtelPaymentProvider\Services;

use App\Plugins\AirtelPaymentProvider\Models\AirtelCredential;
use App\Traits\EncryptsCredentials;

class AirtelCredentialService {
    use EncryptsCredentials;

    private const array ENCRYPTED_FIELDS = ['jwt_secret'];

    public function __construct(
        private AirtelCredential $credential,
    ) {}

    public function getCredentials(): AirtelCredential {
        $credential = $this->credential->newQuery()->firstOrCreate([], [
            'jwt_secret' => null,
            'phone_prefix' => null,
        ]);

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * @param array{jwt_secret: string, phone_prefix: string} $data
     */
    public function updateCredentials(array $data): AirtelCredential {
        $this->credential->newQuery()->firstOrCreate([])
            ->update($this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS));

        return $this->getCredentials();
    }
}
