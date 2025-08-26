<?php

namespace Inensus\MesombPaymentProvider\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\ManufacturerTransactionInterface;
use App\Models\Transaction\PaymentProviderTransactionInterface;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @implements PaymentProviderTransactionInterface<MesombTransaction>
 */
class MesombTransaction extends BaseModel implements PaymentProviderTransactionInterface {
    protected $table = 'mesomb_transactions';
    public const RELATION_NAME = 'mesomb_transactions';

    /**
     * @return MorphOne<Transaction, MesombTransaction>
     */
    public function transaction(): MorphOne {
        /** @var MorphOne<Transaction, MesombTransaction> */
        $relation = $this->morphOne(Transaction::class, 'original_transaction');

        return $relation;
    }

    /**
     * @return MorphTo<Model&ManufacturerTransactionInterface, MesombTransaction>
     */
    public function manufacturerTransaction(): MorphTo {
        /** @var MorphTo<Model&ManufacturerTransactionInterface, MesombTransaction> */
        $relation = $this->morphTo();

        return $relation;
    }

    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
