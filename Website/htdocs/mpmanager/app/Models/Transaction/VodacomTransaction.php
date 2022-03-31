<?php

namespace App\Models\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property mixed conversation_id
 * @property mixed originator_conversation_id
 * @property mixed id
 * @property mixed transaction_id
 * @property string mpesa_receipt
 * @property string transaction_date
 * @property int status
 */
class VodacomTransaction extends BaseModel implements IRawTransaction
{
    protected $connection = 'test_company_db';
    /**
     * @return MorphOne
     */
    public function transaction()
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
