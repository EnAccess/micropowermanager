<?php
namespace Inensus\MicroStarMeter\Services;

use Inensus\MicroStarMeter\Models\MicroStarCredential;

class MicroStarCredentialService
{

    public function __construct(
       private MicroStarCredential $credential
    ) {
    }

    /**
     * This function uses one time on installation of the package.
     *
     */
    public function createCredentials()
    {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'user_id' => null,
            'api_key' => null,
        ]);
    }

    public function getCredentials()
    {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data)
    {
        $credential = $this->credential->newQuery()->find($data['id']);
        $credential->update([
            'user_id' => $data['user_id'],
            'api_key' => $data['api_key'],
        ]);
        return $credential->fresh();
    }
}
