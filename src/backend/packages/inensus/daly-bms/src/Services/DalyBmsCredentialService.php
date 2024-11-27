<?php

namespace Inensus\DalyBms\Services;

use Inensus\DalyBms\Models\DalyBmsCredential;

class DalyBmsCredentialService {
    public function __construct(
        private DalyBmsCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_name' => null,
            'password' => null,
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
