<?php

namespace App\Plugins\SwiftaPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      string|null                           $transaction_reference
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property      int                                   $status
 * @property      float                                 $amount
 * @property      string                                $cipher
 * @property      string                                $timestamp
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Collection<int, TransactionConflicts> $conflicts
 * @property-read Model|null                            $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
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
