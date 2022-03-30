<?php

namespace App\Models\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ThirdPartyTransaction extends BaseModel implements IRawTransaction
{

    protected $connection = 'test_company_db';
    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

    public function manufacturerTransaction(): MorphTo
    {
        return $this->morphTo();
    }

    public function conflicts(): MorphMany
    {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }
}
