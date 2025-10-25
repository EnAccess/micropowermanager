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
    public function createCredentials(): ProspectCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_url' => 'https://demo.prospect.energy/api/v1/in/installations',
            'api_token' => null,
        ]);
    }

    public function getCredentials(): ?ProspectCredential {
        return $this->credential->newQuery()->first();
    }

    /**
     * @param array{api_url: string, api_token: string|null} $data
     */
    public function updateCredentials(array $data): ProspectCredential {
        $credential = $this->credential->newQuery()->first();

        if (!$credential) {
            $credential = $this->createCredentials();
        }

        $credential->update([
            'api_url' => $data['api_url'],
            'api_token' => $data['api_token'],
        ]);

        return $credential->fresh();
    }
}
