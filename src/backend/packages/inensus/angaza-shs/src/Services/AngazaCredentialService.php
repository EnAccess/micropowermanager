<?php

namespace Inensus\AngazaSHS\Services;

use Inensus\AngazaSHS\Models\AngazaCredential;

class AngazaCredentialService {
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
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($credentials, $updateData) {
        $credentials->update($updateData);

        return $credentials->fresh();
    }

    public function getById($id) {
        return $this->credential->newQuery()->findOrFail($id);
    }

    public function isAccessTokenValid($credential) {
        $accessToken = $credential->getAccessToken();

        if ($accessToken == null) {
            return false;
        }
        $tokenExpirationTime = $credential->getExpirationTime();

        if ($tokenExpirationTime == null || $tokenExpirationTime < time()) {
            return false;
        }

        return true;
    }
}
