<?php

namespace Inensus\MicroStarMeter\Services;

use Inensus\MicroStarMeter\Models\MicroStarCredential;

class MicroStarCredentialService {
    public function __construct(
        private MicroStarCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_url' => null,
            'certificate_password' => null,
            'certificate_file_name' => null,
            'certificate_path' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->getCredentials();
        $credential->update([
            'certificate_password' => $data['certificate_password'],
            'api_url' => $data['api_url'],
        ]);

        return $credential->fresh();
    }
}
