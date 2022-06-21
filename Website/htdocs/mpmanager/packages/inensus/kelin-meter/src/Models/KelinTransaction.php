<?php

namespace Inensus\KelinMeter\Models;

use App\Models\BaseModel;
use App\Models\Transaction\ISubTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class KelinTransaction extends BaseModel implements ISubTransaction
{
    protected $table = 'kelin_transactions';


    public function agentTransaction(): MorphOne
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
}
