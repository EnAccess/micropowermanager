<?php

namespace App\Plugins\KelinMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Illuminate\Support\Carbon;

/**
 * @property      int                        $id
 * @property      string                     $meter_serial
 * @property      float                      $amount
 * @property      int                        $op_type
 * @property      string                     $pay_kwh
 * @property      string                     $open_token_1
 * @property      string                     $open_token_2
 * @property      string                     $pay_token
 * @property      string|null                $hash
 * @property      Carbon|null                $created_at
 * @property      Carbon|null                $updated_at
 * @property-read AgentTransaction|null      $agentTransaction
 * @property-read MesombTransaction|null     $mesombTransaction
 * @property-read PaystackTransaction|null   $paystackTransaction
 * @property-read SwiftaTransaction|null     $swiftaTransaction
 * @property-read ThirdPartyTransaction|null $thirdPartyTransaction
 * @property-read WaveMoneyTransaction|null  $waveMoneyTransaction
 */
class KelinTransaction extends BaseManufacturerTransaction {
    protected $table = 'kelin_transactions';
}
