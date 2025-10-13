<?php

namespace Inensus\DalyBms\Services;

use App\Traits\EncryptsCredentials;
use Inensus\DalyBms\Models\DalyBmsCredential;

class DalyBmsCredentialService {
    use EncryptsCredentials;

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
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->user_name) {
                $credential->user_name = $this->decryptCredentialField($credential->user_name);
            }
            if ($credential->password) {
                $credential->password = $this->decryptCredentialField($credential->password);
            }
            if ($credential->access_token) {
                $credential->access_token = $this->decryptCredentialField($credential->access_token);
            }
        }

        return $credential;
    }

    public function updateCredentials(object $credentials, array $updateData): object {
        $encryptedData = $this->encryptCredentialFields($updateData, ['user_name', 'password', 'access_token']);
        $credentials->update($encryptedData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['user_name', 'password', 'access_token']);
    }

    public function getById($id) {
        $credential = $this->credential->newQuery()->findOrFail($id);

        // Decrypt sensitive fields
        if ($credential->user_name) {
            $credential->user_name = $this->decryptCredentialField($credential->user_name);
        }
        if ($credential->password) {
            $credential->password = $this->decryptCredentialField($credential->password);
        }
        if ($credential->access_token) {
            $credential->access_token = $this->decryptCredentialField($credential->access_token);
        }

        return $credential;
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
