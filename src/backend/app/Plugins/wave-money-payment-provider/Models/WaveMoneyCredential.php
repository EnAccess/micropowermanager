<?php

namespace Inensus\WaveMoneyPaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $merchant_id
 * @property string|null $merchant_name
 * @property string|null $secret_key
 * @property string|null $callback_url
 * @property string|null $payment_url
 * @property string|null $result_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
