<?php

namespace App\Models\Transaction;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MPM\Transaction\FullySupportedTransactionInterface;

/**
 * Class AirtelTransaction
 *
 * @package  App
 * @property int $id
 * @property string $interface_id
 * @property string $business_number
 * @property string $trans_id
 * @property int $status
 * @property string $tr_id
 */
class AirtelTransaction extends BaseModel implements IRawTransaction, FullySupportedTransactionInterface
{

    public const RELATION_NAME = 'airtel_transaction';
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

    public static function getTransactionName(): string
    {
        return self::RELATION_NAME;
    }
}
