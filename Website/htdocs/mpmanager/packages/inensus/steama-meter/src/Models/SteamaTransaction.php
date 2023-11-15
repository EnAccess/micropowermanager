<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\ISubTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\VodacomTransaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class SteamaTransaction extends BaseModel implements ISubTransaction
{
    protected $table = 'steama_transactions';


    public function site()
    {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }
    public function agentTransaction()
    {
        return $this->morphOne(AgentTransaction::class, 'manufacturer_transaction');
    }

    public function vodacomTransaction()
    {
        return $this->morphOne(VodacomTransaction::class, 'manufacturer_transaction');
    }

    public function airtelTransaction()
    {
        return $this->morphOne(AirtelTransaction::class, 'manufacturer_transaction');
    }

    public function thirdPartyTransaction()
    {
        return $this->morphOne(ThirdPartyTransaction::class, 'manufacturer_transaction');
    }

    public function mesombTransaction()
    {
        return $this->morphOne(MesombTransaction::class, 'manufacturer_transaction');
    }

    public function swiftaTransaction()
    {
        return $this->morphOne(SwiftaTransaction::class, 'manufacturer_transaction');
    }
    public function waveMoneyTransaction()
    {
        return $this->morphOne(WaveMoneyTransaction::class, 'manufacturer_transaction');
    }
}
