<?php

namespace Inensus\Prospect\Services;

use App\Traits\EncryptsCredentials;
use Inensus\Prospect\Models\ProspectCredential;

class ProspectCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private ProspectCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): ProspectCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_url' => config('services.prospect.default_api_url').'installations',
            'api_token' => null,
        ]);
    }

    public function getCredentials(): ?ProspectCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['api_url', 'api_token']);
    }

    /**
     * @param array{api_url: string, api_token: string|null} $data
     */
    public function updateCredentials(array $data): ProspectCredential {
        $credential = $this->credential->newQuery()->first();

        if (!$credential) {
            $credential = $this->createCredentials();
        }

        $encryptedData = $this->encryptCredentialFields($data, ['api_url', 'api_token']);
        $credential->update($encryptedData);

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['api_url', 'api_token']);
    }
}
