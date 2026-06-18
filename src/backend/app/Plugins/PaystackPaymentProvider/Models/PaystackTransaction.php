<?php

namespace App\Plugins\PaystackPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      float                                 $amount
 * @property      string                                $currency
 * @property      string                                $order_id
 * @property      string                                $reference_id
 * @property      int                                   $status
 * @property      string|null                           $external_transaction_id
 * @property      int                                   $customer_id
 * @property      string|null                           $serial_id
 * @property      string|null                           $device_type
 * @property      string|null                           $paystack_reference
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property      string|null                           $payment_url
 * @property      array<array-key, mixed>|null          $metadata
 * @property      int                                   $attempts
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Collection<int, TransactionConflicts> $conflicts
 * @property-read Model|\Eloquent|null                  $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class PaystackTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'paystack_transaction';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_ABANDONED = 3;
    public const MAX_ATTEMPTS = 5;

    protected $table = 'paystack_transactions';

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array {
        return $this->metadata ?? [];
    }

    public function getDeviceType(): string {
        return $this->device_type ?? 'meter';
    }

    public function setAmount(float $amount): void {
        $this->amount = (int) $amount;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
    }

    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

    public function manufacturerTransaction(): MorphTo {
        return $this->morphTo();
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
