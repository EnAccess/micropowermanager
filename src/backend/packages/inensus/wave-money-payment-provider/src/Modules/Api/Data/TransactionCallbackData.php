<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api\Data;

class TransactionCallbackData {
    public const STATUS_PAYMENT_CONFIRMED = 'PAYMENT_CONFIRMED';
    public const STATUS_BILL_COLLECTION_FAILED = 'BILL_COLLECTION_FAILED';
    public const STATUS_INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    public const STATUS_ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    public const STATUS_SCHEDULER_TRANSACTION_TIMED_OUT = 'SCHEDULER_TRANSACTION_TIMED_OUT';
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = -1;

    public function __construct(
        private string $status,
        private string $merchantId,
        private string $orderId,
        private string $merchantReferenceId,
        private string $frontendResultUrl,
        private string $backEndResultUrl,
        private ?string $initiatorMsisdn,
        private float $amount,
        private int $timeToLiveSeconds,
        private string $paymentDescription,
        private string $currency,
        private string $hash,
        private ?string $transactionId,
        private string|int $paymentRequestId,
        private string $requestTime,
        private ?string $additional1,
        private ?string $additional2,
        private ?string $additional3,
        private ?string $additional4,
        private ?string $additional5,
    ) {}

    public function getStatus(): string {
        return $this->status;
    }

    public function getMerchantId(): string {
        return $this->merchantId;
    }

    public function getOrderId(): string {
        return $this->orderId;
    }

    public function getMerchantReferenceId(): string {
        return $this->merchantReferenceId;
    }

    public function getFrontendResultUrl(): string {
        return $this->frontendResultUrl;
    }

    public function getBackEndResultUrl(): string {
        return $this->backEndResultUrl;
    }

    public function getInitiatorMsisdn(): ?string {
        return $this->initiatorMsisdn;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getTimeToLiveSeconds(): int {
        return $this->timeToLiveSeconds;
    }

    public function getPaymentDescription(): string {
        return $this->paymentDescription;
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function getTransactionId(): ?string {
        return $this->transactionId;
    }

    public function getPaymentRequestId(): string|int {
        return $this->paymentRequestId;
    }

    public function getRequestTime(): string {
        return $this->requestTime;
    }

    public function getAdditionalFields(): array {
        return [
            'add1' => $this->additional1,
            'add2' => $this->additional2,
            'add3' => $this->additional3,
            'add4' => $this->additional4,
            'add5' => $this->additional5,
        ];
    }

    public function mapTransactionStatus(string $status): int {
        if ($status === self::STATUS_PAYMENT_CONFIRMED) {
            return self::STATUS_SUCCESS;
        }

        return self::STATUS_FAILURE;
    }
}
