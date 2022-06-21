<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Transaction\ISubTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SteamaTransaction extends BaseModel implements ISubTransaction
{
    protected $table = 'steama_transactions';


    public function category()
    {
        return $this->belongsTo(SteamaTransactionCategory::class, 'category_id');
    }
    public function site()
    {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
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
