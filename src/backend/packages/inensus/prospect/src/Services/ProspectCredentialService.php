<?php

namespace Inensus\Prospect\Services;

use Inensus\Prospect\Models\ProspectCredential;

class ProspectCredentialService {
    public function __construct(
        private ProspectCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_url' => 'https://demo.prospect.energy/api/v1/in/installations',
            'api_token' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->first();

        if (!$credential) {
            // Create credential if it doesn't exist
            $credential = $this->createCredentials();
        }

        $baseUrl = rtrim(config('prospect.api_uri', 'https://demo.prospect.energy/api/v1/in/'), '/');
        $endpoint = ltrim($data['api_url'], '/');
        $normalizedApiUrl = $baseUrl . '/' . $endpoint;

        $credential->update([
            'api_url' => $normalizedApiUrl,
            'api_token' => $data['api_token'],
        ]);
        $credential->save();

        return $credential->fresh();
    }
}
