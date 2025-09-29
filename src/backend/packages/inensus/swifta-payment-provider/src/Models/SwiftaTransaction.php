<?php

namespace Inensus\SwiftaPaymentProvider\Models;

use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string                            $transaction_reference
 * @property Model&BaseManufacturerTransaction $manufacturerTransaction
 * @property int                               $status
 * @property float                             $amount
 * @property string                            $cipher
 */
class SwiftaTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'swifta_transaction';
    public const STATUS_SUCCESS = 1;
    public const STATUS_PENDING = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_REQUESTED = -2;

    protected $table = 'swifta_transactions';

    public function getAmount(): float {
        return $this->amount;
    }

    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
