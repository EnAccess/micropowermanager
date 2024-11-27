<?php

namespace Inensus\CalinMeter\Services;

use Inensus\CalinMeter\Models\CalinCredential;

class CalinCredentialService {
    private $credential;

    public function __construct(
        CalinCredential $credentialModel,
    ) {
        $this->credential = $credentialModel;
    }

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
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->firstOrFail();
        $credential->update([
            'user_id' => $data['user_id'],
            'api_key' => $data['api_key'],
        ]);

        return $credential->fresh();
    }
}
