<?php

namespace App\Plugins\CalinMeter\Services;

use App\Plugins\CalinMeter\Models\CalinCredential;
use App\Traits\EncryptsCredentials;

class CalinCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private CalinCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): CalinCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_id' => null,
            'api_key' => null,
        ]);
    }

    public function getCredentials(): ?CalinCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['user_id', 'api_key']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): CalinCredential {
        $credential = $this->credential->newQuery()->firstOrFail();
        $encryptedData = $this->encryptCredentialFields($data, ['user_id', 'api_key']);

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['user_id', 'api_key']);
    }
}
