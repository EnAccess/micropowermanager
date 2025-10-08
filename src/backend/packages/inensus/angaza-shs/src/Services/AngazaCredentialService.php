<?php

namespace Inensus\AngazaSHS\Services;

use App\Traits\EncryptsCredentials;
use Inensus\AngazaSHS\Models\AngazaCredential;

class AngazaCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private AngazaCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'client_id' => null,
            'client_secret' => null,
        ]);
    }

    public function getCredentials() {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['client_id', 'client_secret']);
    }

    public function updateCredentials($credentials, $updateData) {
        $encryptedData = $this->encryptCredentialFields($updateData, ['client_id', 'client_secret']);

        $credentials->update($encryptedData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['client_id', 'client_secret']);
    }

    public function getById($id) {
        $credential = $this->credential->newQuery()->findOrFail($id);

        return $this->decryptCredentialFields($credential, ['client_id', 'client_secret']);
    }

    public function isAccessTokenValid($credential): bool {
        $accessToken = $credential->getAccessToken();

        if ($accessToken == null) {
            return false;
        }
        $tokenExpirationTime = $credential->getExpirationTime();

        return $tokenExpirationTime != null && $tokenExpirationTime >= time();
    }
}
