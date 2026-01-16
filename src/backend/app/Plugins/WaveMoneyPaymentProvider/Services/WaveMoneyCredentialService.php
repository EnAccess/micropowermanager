<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Services;

use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;
use App\Traits\EncryptsCredentials;

class WaveMoneyCredentialService {
    use EncryptsCredentials;

    public function __construct(
        private WaveMoneyCredential $credential,
    ) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createCredentials(): WaveMoneyCredential {
        return $this->credential->newQuery()->firstOrCreate(['id' => 1], [
            'merchant_id' => null,
            'secret_key' => null,
            'callback_url' => null,
            'payment_url' => null,
            'result_url' => null,
            'merchant_name' => null,
        ]);
    }

    public function getCredentials(): ?WaveMoneyCredential {
        $credential = $this->credential->newQuery()->first();

        if ($credential) {
            // Decrypt sensitive fields
            if ($credential->merchant_id) {
                $credential->merchant_id = $this->decryptCredentialField($credential->merchant_id);
            }
            if ($credential->merchant_name) {
                $credential->merchant_name = $this->decryptCredentialField($credential->merchant_name);
            }
            if ($credential->secret_key) {
                $credential->secret_key = $this->decryptCredentialField($credential->secret_key);
            }
            if ($credential->callback_url) {
                $credential->callback_url = $this->decryptCredentialField($credential->callback_url);
            }
            if ($credential->payment_url) {
                $credential->payment_url = $this->decryptCredentialField($credential->payment_url);
            }
            if ($credential->result_url) {
                $credential->result_url = $this->decryptCredentialField($credential->result_url);
            }
        }

        return $credential;
    }

    /**
     * @param array{
     *     id: int,
     *     merchant_id: string,
     *     secret_key: string,
     *     callback_url: string,
     *     payment_url: string,
     *     result_url: string,
     *     merchant_name: string
     * } $data
     */
    public function updateCredentials(array $data): WaveMoneyCredential {
        $credential = $this->credential->newQuery()->find($data['id']);

        $encryptedData = $this->encryptCredentialFields($data, ['merchant_id', 'secret_key', 'callback_url', 'payment_url', 'result_url', 'merchant_name']);
        $credential->update($encryptedData);
        $credential->save();

        $credential->fresh();

        return $this->decryptCredentialFields($credential, ['merchant_id', 'secret_key', 'callback_url', 'payment_url', 'result_url', 'merchant_name']);
    }
}
