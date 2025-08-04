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
     * @phpstan-return MorphOne<Transaction, Model&PaymentProviderTransactionInterface>
     */
    public function transaction(): MorphOne {
        /** @var MorphOne<Transaction, Model&PaymentProviderTransactionInterface> $relation */
        $relation = $this->morphOne(Transaction::class, 'original_transaction');

        return $relation;
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function manufacturerTransaction(): MorphTo {
        return $this->morphTo();
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
