<?php

namespace Inensus\TextbeeSmsGateway\Services;

use App\Traits\EncryptsCredentials;
use Inensus\TextbeeSmsGateway\Models\TextbeeCredential;

class TextbeeCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private TextbeeCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): TextbeeCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'device_id' => null,
        ]);
    }

    public function getCredentials(): ?TextbeeCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['api_key', 'device_id']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): TextbeeCredential {
        $credential = $this->credential->newQuery()->find($data['id']);

        $encryptedData = $this->encryptCredentialFields($data, ['api_key', 'device_id']);

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['api_key', 'device_id']);
    }
}
