<?php

namespace App\Plugins\AfricasTalking\Services;

use App\Plugins\AfricasTalking\Models\AfricasTalkingCredential;
use App\Traits\EncryptsCredentials;

class AfricasTalkingCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private AfricasTalkingCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): AfricasTalkingCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'username' => null,
            'short_code' => null,
        ]);
    }

    public function getCredentials(): ?AfricasTalkingCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['api_key', 'username', 'short_code']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): AfricasTalkingCredential {
        $credential = $this->credential->newQuery()->find($data['id']);

        $encryptedData = $this->encryptCredentialFields($data, ['api_key', 'username', 'short_code']);

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['api_key', 'username', 'short_code']);
    }
}
