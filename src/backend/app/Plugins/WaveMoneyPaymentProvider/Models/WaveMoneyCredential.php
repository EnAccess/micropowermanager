<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Models;

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
}
