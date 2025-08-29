<?php

namespace App\Models\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ThirdPartyTransaction extends BaseModel implements PaymentProviderTransactionInterface {
    public const RELATION_NAME = 'third_party_transaction';

    /**
     * @return MorphOne<Transaction, $this>
     */
    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'original_transaction');
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
