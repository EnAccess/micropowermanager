<?php

namespace Inensus\AngazaSHS\Services;

use App\Traits\EncryptsCredentials;
use Inensus\AngazaSHS\Models\AngazaCredential;

class AngazaCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private AngazaCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): AngazaCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'client_id' => null,
            'client_secret' => null,
        ]);
    }

    public function getCredentials(): AngazaCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['client_id', 'client_secret']);
    }

    /**
     * @param array<string, mixed> $updateData
     */
    public function updateCredentials(AngazaCredential $credentials, array $updateData): AngazaCredential {
        $encryptedData = $this->encryptCredentialFields($updateData, ['client_id', 'client_secret']);

        $credentials->update($encryptedData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['client_id', 'client_secret']);
    }

    public function getById(int $id): AngazaCredential {
        $credential = $this->credential->newQuery()->findOrFail($id);

        return $this->decryptCredentialFields($credential, ['client_id', 'client_secret']);
    }
}
