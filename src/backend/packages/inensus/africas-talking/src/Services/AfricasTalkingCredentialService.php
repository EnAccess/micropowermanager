<?php

namespace Inensus\AfricasTalking\Services;

use Inensus\AfricasTalking\Models\AfricasTalkingCredential;

class AfricasTalkingCredentialService {
    public function __construct(
        private AfricasTalkingCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_key' => null,
            'username' => null,
            'short_code' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->find($data['id']);

        $credential->update([
            'api_key' => $data['api_key'],
            'username' => $data['username'],
            'short_code' => $data['short_code'],
        ]);
        $credential->save();

        return $credential->fresh();
    }
}
