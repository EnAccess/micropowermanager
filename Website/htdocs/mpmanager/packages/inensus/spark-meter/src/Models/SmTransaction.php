<?php

namespace Inensus\SparkMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\ISubTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SmTransaction extends BaseModel implements ISubTransaction
{
    protected $table = 'sm_transactions';

    public function mpmTransaction()
    {
        return $this->belongsTo(Transaction::class, 'mpm_transaction_id');
    }

    public function site()
    {
        return $this->belongsTo(SmSite::class, 'site_id', 'site_id');
    }
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
