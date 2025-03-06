<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\ISubTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class SmTransaction extends BaseModel implements ISubTransaction {
    protected $table = 'sm_transactions';

    public function mpmTransaction() {
        return $this->belongsTo(Transaction::class, 'mpm_transaction_id');
    }

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }

    public function agentTransaction() {
        return $this->morphOne(AgentTransaction::class, 'manufacturer_transaction');
    }

    public function thirdPartyTransaction() {
        return $this->morphOne(ThirdPartyTransaction::class, 'manufacturer_transaction');
    }

    public function mesombTransaction() {
        return $this->morphOne(MesombTransaction::class, 'manufacturer_transaction');
    }

    public function swiftaTransaction() {
        return $this->morphOne(SwiftaTransaction::class, 'manufacturer_transaction');
    }

    public function waveMoneyTransaction() {
        return $this->morphOne(WaveMoneyTransaction::class, 'manufacturer_transaction');
    }
}
