<?php

namespace Inensus\WaveMoneyPaymentProvider\Models;

use App\Models\Base\BaseModel;

/**
 * @property string merchant_id
 * @property string secret_key
 * @property string callback_url
 * @property string payment_url
 * @property string result_url
 * @property string merchant_name
 */
class WaveMoneyCredential extends BaseModel {
    protected $table = 'wave_money_credentials';

    public function getMerchantId(): string {
        return $this->merchant_id;
    }

    public function getSecretKey(): string {
        return $this->secret_key;
    }

    public function getMerchantName(): string {
        return $this->merchant_name;
    }

    public function getCallbackUrl(): string {
        return $this->callback_url;
    }

    public function getPaymentUrl(): string {
        return $this->payment_url;
    }

    public function getResultUrl(): string {
        return $this->result_url;
    }
}
