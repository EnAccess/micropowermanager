<?php

namespace Inensus\CalinSmartMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\ManufacturerTransactionInterface;
use App\Models\Transaction\ThirdPartyTransaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class CalinSmartTransaction extends BaseModel implements ManufacturerTransactionInterface {
    protected $table = 'calin_smart_transactions';

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
