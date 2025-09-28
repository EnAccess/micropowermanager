<?php

namespace Inensus\MesombPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MesombTransaction extends BasePaymentProviderTransaction {
    protected $table = 'mesomb_transactions';
    public const RELATION_NAME = 'mesomb_transactions';


    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
