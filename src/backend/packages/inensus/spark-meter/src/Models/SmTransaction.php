<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * @property Transaction           $mpmTransaction
 * @property AgentTransaction      $agentTransaction
 * @property ThirdPartyTransaction $thirdPartyTransaction
 * @property MesombTransaction     $mesombTransaction
 * @property SwiftaTransaction     $swiftaTransaction
 * @property WaveMoneyTransaction  $waveMoneyTransaction
 */
class SmTransaction extends BaseManufacturerTransaction {
    protected $table = 'sm_transactions';

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }

    public function mpmTransaction() {
        return $this->belongsTo(Transaction::class, 'mpm_transaction_id');
    }
}
