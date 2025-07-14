<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\ManufacturerTransactionInterface;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class SmTransaction extends BaseModel implements ManufacturerTransactionInterface {
    protected $table = 'sm_transactions';

    public function mpmTransaction() {
        return $this->belongsTo(Transaction::class, 'mpm_transaction_id');
    }

    public function site() {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }

    public function agentTransaction(): MorphOne {
        return $this->morphOne(AgentTransaction::class, 'manufacturer_transaction');
    }

    public function thirdPartyTransaction(): MorphOne {
        return $this->morphOne(ThirdPartyTransaction::class, 'manufacturer_transaction');
    }

    public function mesombTransaction(): MorphOne {
        return $this->morphOne(MesombTransaction::class, 'manufacturer_transaction');
    }

    public function swiftaTransaction(): MorphOne {
        return $this->morphOne(SwiftaTransaction::class, 'manufacturer_transaction');
    }

    public function waveMoneyTransaction(): MorphOne {
        return $this->morphOne(WaveMoneyTransaction::class, 'manufacturer_transaction');
    }
}
