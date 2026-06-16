<?php

namespace App\Plugins\VodacomMzPaymentProvider\Services;

use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzCredential;
use App\Traits\EncryptsCredentials;

class VodacomMzCredentialService {
    use EncryptsCredentials;

    /** Fields stored encrypted at rest. */
    private const array ENCRYPTED_FIELDS = ['api_key'];

    public function __construct(
        private VodacomMzCredential $credential,
    ) {}

    public function getCredentials(): VodacomMzCredential {
        $credential = $this->credential->newQuery()->firstOrCreate([], [
            'api_key' => null,
            'public_key' => null,
            'service_provider_code' => null,
            'live' => false,
        ]);

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }

    /**
     * @param array{
     *     api_key?: string|null,
     *     public_key?: string|null,
     *     service_provider_code?: string|null,
     *     live?: bool
     * } $data
     */
    public function updateCredentials(array $data): VodacomMzCredential {
        $credential = $this->credential->newQuery()->firstOrCreate([]);

        $encryptedData = $this->encryptCredentialFields($data, self::ENCRYPTED_FIELDS);

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, self::ENCRYPTED_FIELDS);
    }
}
