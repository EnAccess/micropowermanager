<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class ThirdPartyTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'third_party_transaction';

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
