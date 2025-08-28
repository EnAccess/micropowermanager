<?php

namespace Inensus\PaystackPaymentProvider\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\PaymentProviderTransactionInterface;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int id
 * @property int amount
 * @property string currency
 * @property string order_id
 * @property string reference_id
 * @property string status
 * @property string external_transaction_id
 * @property int customer_id
 * @property string|null meter_serial
 * @property string|null paystack_reference
 * @property string|null payment_url
 * @property array|null metadata
 */
class PaystackTransaction extends BaseModel implements PaymentProviderTransactionInterface {
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

    public function getMeterSerial(): string {
        return $this->serial_id;
    }

    public function getCustomerId(): int {
        return $this->customer_id;
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
        $this->serial_id = $meterSerialNumber;
    }

    public function setAmount(float $amount): void {
        $this->amount = $amount;
    }

    public function setCurrency(string $currency): void {
        $this->currency = $currency;
    }

    public function setPaystackReference(string $reference): void {
        $this->paystack_reference = $reference;
    }

    public function setPaymentUrl(string $url): void {
        $this->payment_url = $url;
    }

    public function setMetadata(array $metadata): void {
        $this->metadata = $metadata;
    }

    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'original_transaction');
    }

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
