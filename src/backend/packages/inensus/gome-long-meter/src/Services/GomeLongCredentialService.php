<?php

namespace Inensus\GomeLongMeter\Services;

use App\Traits\EncryptsCredentials;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Inensus\GomeLongMeter\Models\GomeLongCredential;
use InvalidArgumentException;

class GomeLongCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private GomeLongCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): GomeLongCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_id' => null,
            'user_password' => null,
        ]);
    }

    public function getCredentials(): ?GomeLongCredential {
        $credential = $this->credential->newQuery()->first();

        return $this->decryptCredentialFields($credential, ['user_id', 'user_password']);
    }

    /**
     * @param array<string, mixed> $updateData
     */
    public function updateCredentials(GomeLongCredential $credentials, array $updateData): GomeLongCredential {
        $credentials->update($updateData);

        $credentials->fresh();

        return $this->decryptCredentialFields($credentials, ['user_id', 'user_password']);
    }

    public function getById(int $id): GomeLongCredential {
        $credential = $this->credential->newQuery()->findOrFail($id);

        return $this->decryptCredentialFields($credential, ['user_id', 'user_password']);
    }
}
