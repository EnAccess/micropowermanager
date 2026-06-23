<?php

namespace App\Plugins\SafaricomMobileMoney\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property      string                                $phone_number
 * @property      string|null                           $checkout_request_id
 * @property      string|null                           $merchant_request_id
 * @property      string|null                           $mpesa_receipt_number
 * @property      Carbon|null                           $transaction_date
 * @property      string|null                           $account_reference
 * @property      string|null                           $transaction_desc
 * @property      array<array-key, mixed>|null          $response_data
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property      array<array-key, mixed>|null          $metadata
 * @property      int                                   $attempts
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Collection<int, TransactionConflicts> $conflicts
 * @property-read Model|\Eloquent|null                  $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class SafaricomTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'safaricom_transaction';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_ABANDONED = 3;
    public const MAX_ATTEMPTS = 5;

    protected $table = 'safaricom_transactions';

    protected $casts = [
        'metadata' => 'array',
        'response_data' => 'array',
        'transaction_date' => 'datetime',
    ];

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getOrderId(): string {
        return $this->order_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array {
        return $this->metadata ?? [];
    }

    public function getReferenceId(): string {
        return $this->reference_id;
    }

    public function getDeviceSerial(): string {
        return $this->serial_id ?? '';
    }

    public function getCustomerId(): int {
        return $this->customer_id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getDeviceType(): string {
        return $this->device_type ?? 'meter';
    }

    public function getStatus(): int {
        return $this->status;
    }

    public function getPhoneNumber(): string {
        return $this->phone_number;
    }

    public function getCheckoutRequestId(): ?string {
        return $this->checkout_request_id;
    }

    public function getMerchantRequestId(): ?string {
        return $this->merchant_request_id;
    }

    public function getMpesaReceiptNumber(): ?string {
        return $this->mpesa_receipt_number;
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

    public function setDeviceType(string $deviceType): void {
        $this->device_type = $deviceType;
    }

    public function setDeviceSerial(string $deviceSerialNumber): void {
        $this->serial_id = $deviceSerialNumber;
    }

    public function setAmount(float $amount): void {
        $this->amount = $amount;
    }

    public function setCurrency(string $currency): void {
        $this->currency = $currency;
    }

    public function setPhoneNumber(string $phoneNumber): void {
        $this->phone_number = $phoneNumber;
    }

    public function setCheckoutRequestId(string $checkoutRequestId): void {
        $this->checkout_request_id = $checkoutRequestId;
    }

    public function setMerchantRequestId(string $merchantRequestId): void {
        $this->merchant_request_id = $merchantRequestId;
    }

    public function setMpesaReceiptNumber(string $receipt): void {
        $this->mpesa_receipt_number = $receipt;
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
