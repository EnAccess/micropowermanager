<?php

namespace App\Plugins\MicroStarMeter\Services;

use App\Plugins\MicroStarMeter\Models\MicroStarCredential;
use App\Traits\EncryptsCredentials;

class MicroStarCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private MicroStarCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): MicroStarCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'api_url' => null,
            'certificate_password' => null,
            'certificate_file_name' => null,
            'certificate_path' => null,
        ]);
    }

    public function getCredentials(): ?MicroStarCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['certificate_file_name', 'certificate_path', 'certificate_password']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): MicroStarCredential {
        $credential = $this->getCredentials();

        $encryptedData = $this->encryptCredentialFields($data, ['certificate_password']);
        $encryptedData['api_url'] = $data['api_url'];

        $credential->update($encryptedData);

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['certificate_file_name', 'certificate_path', 'certificate_password']);
    }
}
