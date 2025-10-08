<?php

namespace Inensus\MicroStarMeter\Services;

use App\Traits\EncryptsCredentials;
use Inensus\MicroStarMeter\Models\MicroStarCredential;

class MicroStarCredentialService {
    use EncryptsCredentials;

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

    public function getCredentials(): object {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['certificate_file_name', 'certificate_path', 'certificate_password']);
    }

    public function updateCredentials(array $data): object {
        $credential = $this->getCredentials();

        $encryptedData = $this->encryptCredentialFields($data, ['certificate_password']);
        $encryptedData['api_url'] = $data['api_url'];

        $credential->update($encryptedData);

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['certificate_file_name', 'certificate_path', 'certificate_password']);
    }
}
