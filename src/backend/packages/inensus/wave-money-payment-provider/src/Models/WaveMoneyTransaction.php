<?php

namespace Inensus\WaveMoneyPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int         $id
 * @property int         $amount
 * @property string      $currency
 * @property string      $order_id
 * @property string      $reference_id
 * @property int         $status
 * @property string      $external_transaction_id
 * @property int         $customer_id
 * @property string|null $meter_serial
 */
class WaveMoneyTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'wave_money_transaction';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED_BY_WAVE_MONEY = 2;
    public const MAX_ATTEMPTS = 5;

    protected $table = 'wave_money_transactions';

    public function getAmount(): int {
        return $this->amount;
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getOrderId(): string {
        return $this->order_id;
    }

    public function getReferenceId(): string {
        return $this->reference_id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setStatus(int $status): void {
        $this->status = $status;
    }

    public function setExternalTransactionId(string $transactionId): void {
        $this->external_transaction_id = $transactionId;
    }

    public function setOrderId(string $orderId): void {
        $this->order_id = $orderId;
    }

    public function setReferenceId(string $referenceId): void {
        $this->reference_id = $referenceId;
    }

    public function setCustomerId(int $customerId): void {
        $this->customer_id = $customerId;
    }

    public function setMeterSerial(string $meterSerialNumber): void {
        $this->meter_serial = $meterSerialNumber;
    }

    public function setAmount(int $amount): void {
        $this->amount = $amount;
    }

    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
