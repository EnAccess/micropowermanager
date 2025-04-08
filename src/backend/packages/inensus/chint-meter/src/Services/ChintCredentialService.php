<?php

namespace Inensus\ChintMeter\Services;

use Inensus\ChintMeter\Models\ChintCredential;

class ChintCredentialService {
    public function __construct(
        private ChintCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_name' => null,
            'user_password' => null,
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
}
