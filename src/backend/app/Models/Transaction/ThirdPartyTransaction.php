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
     * @phpstan-return MorphOne<Transaction, Model&PaymentProviderTransactionInterface>
     */
    public function transaction(): MorphOne {
        /** @var MorphOne<Transaction, Model&PaymentProviderTransactionInterface> $relation */
        $relation = $this->morphOne(Transaction::class, 'original_transaction');

        return $relation;
    }

    /**
     * @return MorphTo<BaseModel, $this>
     */
    public function manufacturerTransaction(): MorphTo {
        /** @var MorphTo<BaseModel, $this> $relation */
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
