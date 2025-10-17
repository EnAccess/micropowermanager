<?php

namespace Inensus\ChintMeter\Services;

use App\Traits\EncryptsCredentials;
use Inensus\ChintMeter\Models\ChintCredential;

class ChintCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private ChintCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): ChintCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_name' => null,
            'user_password' => null,
        ]);
    }

    public function getCredentials(): ChintCredential {
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->user_name) {
                $credential->user_name = $this->decryptCredentialField($credential->user_name);
            }
            if ($credential->user_password) {
                $credential->user_password = $this->decryptCredentialField($credential->user_password);
            }
        }

        return $credential;
    }

    /**
     * @param array<string, mixed> $updateData
     */
    public function updateCredentials(ChintCredential $credentials, array $updateData): ChintCredential {
        $encryptedData = $this->encryptCredentialFields($updateData, ['user_name', 'user_password']);

        $credentials->update($encryptedData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['user_name', 'user_password']);
    }

    public function getById(int $id): ChintCredential {
        $credential = $this->credential->newQuery()->findOrFail($id);

        // Decrypt sensitive fields
        if ($credential->user_name) {
            $credential->user_name = $this->decryptCredentialField($credential->user_name);
        }
        if ($credential->user_password) {
            $credential->user_password = $this->decryptCredentialField($credential->user_password);
        }

        return $credential;
    }
}
