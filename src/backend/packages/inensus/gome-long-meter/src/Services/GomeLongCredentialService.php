<?php

namespace Inensus\GomeLongMeter\Services;

use Inensus\GomeLongMeter\Models\GomeLongCredential;

class GomeLongCredentialService {
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
