<?php
namespace Inensus\SwiftaPaymentProvider\Models;

use App\Models\BaseModel;
use App\Models\Transaction\IRawTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SwiftaTransaction extends BaseModel implements IRawTransaction
{
    const STATUS_SUCCESS = 1;
    const STATUS_PENDING = 0;
    const STATUS_FAILED = -1;
    const STATUS_REQUESTED = -2;

    protected $table = 'swifta_transactions';

    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return MorphOne
     */
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