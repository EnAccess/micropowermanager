<?php

namespace Inensus\SunKingSHS\Services;

use App\Traits\EncryptsCredentials;
use Inensus\SunKingSHS\Models\SunKingCredential;

class SunKingCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private SunKingCredential $credential,
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

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->client_id) {
                $credential->client_id = $this->decryptCredentialField($credential->client_id);
            }
            if ($credential->client_secret) {
                $credential->client_secret = $this->decryptCredentialField($credential->client_secret);
            }
            if ($credential->access_token) {
                $credential->access_token = $this->decryptCredentialField($credential->access_token);
            }
        }

        return $credential;
    }

    public function updateCredentials(object $credentials, array $updateData): object {
        $encryptedData = $this->encryptCredentialFields($updateData, ['client_id', 'client_secret', 'access_token']);
        $credentials->update($encryptedData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['client_id', 'client_secret', 'access_token']);
    }

    public function getById($id) {
        $credential = $this->credential->newQuery()->findOrFail($id);

        // Decrypt sensitive fields
        if ($credential->client_id) {
            $credential->client_id = $this->decryptCredentialField($credential->client_id);
        }
        if ($credential->client_secret) {
            $credential->client_secret = $this->decryptCredentialField($credential->client_secret);
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
