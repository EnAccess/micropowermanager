<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use App\Models\User;
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

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

    public function manufacturerTransaction(): MorphTo {
        return $this->morphTo();
    }

    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
