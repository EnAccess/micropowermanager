<?php

namespace Inensus\GomeLongMeter\Services;

use App\Traits\EncryptsCredentials;
use Inensus\GomeLongMeter\Models\GomeLongCredential;

class GomeLongCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private GomeLongCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_id' => null,
            'user_password' => null,
        ]);
    }

    public function getCredentials(): ?object {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['user_id', 'user_password']);
    }

    public function updateCredentials(object $credentials, $updateData): object {
        $credentials->update($updateData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['user_id', 'user_password']);
    }

    public function getById($id): object {
        $credential = $this->credential->newQuery()->findOrFail($id);

        return $this->decryptCredentialFields($credential, ['user_id', 'user_password']);
    }
}
