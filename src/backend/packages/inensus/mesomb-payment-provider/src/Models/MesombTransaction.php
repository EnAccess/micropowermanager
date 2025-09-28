<?php

namespace Inensus\MesombPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MesombTransaction extends BasePaymentProviderTransaction {
    protected $table = 'mesomb_transactions';
    public const RELATION_NAME = 'mesomb_transactions';

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

    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
