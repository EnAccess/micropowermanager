<?php

namespace Inensus\SunKingSHS\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use Illuminate\Support\Carbon;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * @property      int                        $id
 * @property      Carbon|null                $created_at
 * @property      Carbon|null                $updated_at
 * @property-read AgentTransaction|null      $agentTransaction
 * @property-read MesombTransaction|null     $mesombTransaction
 * @property-read SwiftaTransaction|null     $swiftaTransaction
 * @property-read ThirdPartyTransaction|null $thirdPartyTransaction
 * @property-read WaveMoneyTransaction|null  $waveMoneyTransaction
 */
class SunKingTransaction extends BaseManufacturerTransaction {
    protected $table = 'sun_king_transactions';
}
