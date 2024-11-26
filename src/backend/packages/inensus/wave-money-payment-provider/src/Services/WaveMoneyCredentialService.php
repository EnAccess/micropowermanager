<?php

namespace Inensus\WaveMoneyPaymentProvider\Services;

use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;

class WaveMoneyCredentialService {
    public function __construct(
        private WaveMoneyCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials() {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'merchant_id' => null,
            'secret_key' => null,
            'callback_url' => null,
            'payment_url' => null,
            'result_url' => null,
            'merchant_name' => null,
        ]);
    }

    public function getCredentials() {
        return $this->credential->newQuery()->first();
    }

    public function updateCredentials($data) {
        $credential = $this->credential->newQuery()->find($data['id']);

        $credential->update([
            'merchant_id' => $data['merchant_id'],
            'secret_key' => $data['secret_key'],
            'callback_url' => $data['callback_url'],
            'payment_url' => $data['payment_url'],
            'result_url' => $data['result_url'],
            'merchant_name' => $data['merchant_name'],
        ]);
        $credential->save();

        return $credential->fresh();
    }
}
