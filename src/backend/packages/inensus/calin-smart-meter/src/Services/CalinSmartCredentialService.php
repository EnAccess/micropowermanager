<?php

namespace Inensus\CalinSmartMeter\Services;

use Inensus\CalinSmartMeter\Models\CalinSmartCredential;

class CalinSmartCredentialService {
    private $credential;

    public function __construct(
        CalinSmartCredential $credentialModel,
    ) {
        $this->credential = $credentialModel;
    }

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'company_name' => null,
            'user_name' => null,
            'password' => null,
            'password_vend' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->find($data['id']);
        $credential->update([
            'company_name' => $data['company_name'],
            'user_name' => $data['user_name'],
            'password' => $data['password'],
            'password_vend' => $data['password_vend'],
        ]);

        return $credential->fresh();
    }
}
