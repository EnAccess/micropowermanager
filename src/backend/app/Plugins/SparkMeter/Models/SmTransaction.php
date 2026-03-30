<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                        $id
 * @property      string                     $site_id
 * @property      string                     $customer_id
 * @property      string                     $transaction_id
 * @property      int|null                   $external_id
 * @property      string                     $status
 * @property      string                     $timestamp
 * @property      Carbon|null                $created_at
 * @property      Carbon|null                $updated_at
 * @property-read AgentTransaction|null      $agentTransaction
 * @property-read MesombTransaction|null     $mesombTransaction
 * @property-read Transaction|null           $mpmTransaction
 * @property-read PaystackTransaction|null   $paystackTransaction
 * @property-read SmSite|null                $site
 * @property-read SwiftaTransaction|null     $swiftaTransaction
 * @property-read ThirdPartyTransaction|null $thirdPartyTransaction
 * @property-read WaveMoneyTransaction|null  $waveMoneyTransaction
 */
class SmTransaction extends BaseManufacturerTransaction {
    protected $table = 'sm_transactions';

    /**
     * @return BelongsTo<SmSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function mpmTransaction(): BelongsTo {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }
}
