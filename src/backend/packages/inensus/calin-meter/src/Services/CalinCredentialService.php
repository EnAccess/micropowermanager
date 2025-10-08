<?php

namespace Inensus\CalinMeter\Services;

use App\Traits\EncryptsCredentials;
use Inensus\CalinMeter\Models\CalinCredential;

class CalinCredentialService {
    use EncryptsCredentials;

    public function __construct(private CalinCredential $credential) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_id' => null,
            'api_key' => null,
        ]);
    }

    public function getCredentials() {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['user_id', 'api_key']);
    }

    public function updateCredentials(array $data) {
        $credential = $this->credential->newQuery()->firstOrFail();
        $encryptedData = $this->encryptCredentialFields($data, ['user_id', 'api_key']);

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['user_id', 'api_key']);
    }
}
