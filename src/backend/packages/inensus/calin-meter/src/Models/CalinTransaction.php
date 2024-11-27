<?php

namespace Inensus\CalinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\ISubTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use MPM\Transaction\Provider\VodacomTransactionProvider;

class CalinTransaction extends BaseModel implements ISubTransaction {
    protected $table = 'calin_transactions';

    public function agentTransaction() {
        return $this->morphOne(AgentTransaction::class, 'manufacturer_transaction');
    }

    public function vodacomTransaction() {
        return $this->morphOne(VodacomTransactionProvider::class, 'manufacturer_transaction');
    }

    public function airtelTransaction() {
        return $this->morphOne(AirtelTransaction::class, 'manufacturer_transaction');
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
