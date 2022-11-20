<?php

namespace Inensus\WaveMoneyPaymentProvider\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;

class WaveMoneyCredentialService
{

    public function __construct(
        private WaveMoneyCredential $credential
    ) {

    }

    /**
     * This function uses one time on installation of the package.
     *
     */
    public function createCredentials()
    {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'merchant_id' => null,
            'secret_key' => null
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
            'merchant_id' => $data['merchant_id'],
            'secret_key' => $data['secret_key'],
        ]);
        $credential->save();

        return $credential->fresh();
    }
}
