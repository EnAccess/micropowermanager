<?php

namespace App\Models\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MPM\Transaction\FullySupportedTransactionInterface;

/**
 * @property mixed conversation_id
 * @property mixed originator_conversation_id
 * @property mixed id
 * @property mixed transaction_id
 * @property string mpesa_receipt
 * @property string transaction_date
 * @property int status
 */
class VodacomTransaction extends BaseModel implements IRawTransaction, FullySupportedTransactionInterface
{
    public const RELATION_NAME = 'vodacom_transaction';

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

    public static function getTransactionName(): string
    {
        return self::RELATION_NAME;
    }
}
