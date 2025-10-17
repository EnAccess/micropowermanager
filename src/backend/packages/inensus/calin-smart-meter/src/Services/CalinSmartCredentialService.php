<?php

namespace Inensus\CalinSmartMeter\Services;

use App\Traits\EncryptsCredentials;
use Inensus\CalinSmartMeter\Models\CalinSmartCredential;

class CalinSmartCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private CalinSmartCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): CalinSmartCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'company_name' => null,
            'user_name' => null,
            'password' => null,
            'password_vend' => null,
        ]);
    }

    public function getCredentials(): ?CalinSmartCredential {
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->company_name) {
                $credential->company_name = $this->decryptCredentialField($credential->company_name);
            }
            if ($credential->user_name) {
                $credential->user_name = $this->decryptCredentialField($credential->user_name);
            }
            if ($credential->password) {
                $credential->password = $this->decryptCredentialField($credential->password);
            }
            if ($credential->password_vend) {
                $credential->password_vend = $this->decryptCredentialField($credential->password_vend);
            }
        }

        return $credential;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateCredentials(array $data): CalinSmartCredential {
        $credential = $this->credential->newQuery()->find($data['id'] ?? null);

        if (!$credential) {
            $credential = $this->createCredentials();
        }

        $encryptedData = $this->encryptCredentialFields($data, ['company_name', 'user_name', 'password', 'password_vend']);

        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['company_name', 'user_name', 'password', 'password_vend']);
    }
}
