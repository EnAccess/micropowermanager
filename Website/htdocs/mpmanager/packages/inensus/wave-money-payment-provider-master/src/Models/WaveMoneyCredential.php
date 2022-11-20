<?php

namespace Inensus\WaveMoneyPaymentProvider\Models;
use App\Models\BaseModel;

/**
 * @property string merchant_id
 * @property string secret_key
 * @property string api_url
 */
class WaveMoneyCredential extends BaseModel
{
    protected $table = 'wave_money_credentials';

    public function getMerchantId(): string
    {
        return $this->merchant_id;
    }

    public function getSecretKey(): string
    {
        return $this->secret_key;
    }
}
