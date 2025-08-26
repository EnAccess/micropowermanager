<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int    $user_id
 * @property int    $status
 * @property int    $manufacturer_transaction_id
 * @property string $manufacturer_transaction_type
 *
 * @implements PaymentProviderTransactionInterface<CashTransaction>
 */
class CashTransaction extends BaseModel implements PaymentProviderTransactionInterface {
    public const RELATION_NAME = 'cash_transaction';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphOne<Transaction, CashTransaction>
     */
    public function transaction(): MorphOne {
        /** @var MorphOne<Transaction, CashTransaction> */
        $relation = $this->morphOne(Transaction::class, 'original_transaction');

        return $relation;
    }

    /**
     * @return MorphTo<Model&ManufacturerTransactionInterface, CashTransaction>
     */
    public function manufacturerTransaction(): MorphTo {
        /** @var MorphTo<Model&ManufacturerTransactionInterface, CashTransaction> */
        $relation = $this->morphTo();

        return $relation;
    }

    /**
     * @return MorphMany<TransactionConflicts, $this>
     */
    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
