<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Data;

class TransactionCallbackData {
    public const STATUS_PAYMENT_CONFIRMED = 'PAYMENT_CONFIRMED';
    public const STATUS_BILL_COLLECTION_FAILED = 'BILL_COLLECTION_FAILED';
    public const STATUS_INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    public const STATUS_ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    public const STATUS_SCHEDULER_TRANSACTION_TIMED_OUT = 'SCHEDULER_TRANSACTION_TIMED_OUT';
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = -1;

    public function __construct(
        public private(set) string $status,
        public private(set) string $merchantId,
        public private(set) string $orderId,
        public private(set) string $merchantReferenceId,
        public private(set) string $frontendResultUrl,
        public private(set) string $backEndResultUrl,
        public private(set) ?string $initiatorMsisdn,
        public private(set) float $amount,
        public private(set) int $timeToLiveSeconds,
        public private(set) string $paymentDescription,
        public private(set) string $currency,
        public private(set) string $hash,
        public private(set) ?string $transactionId,
        public private(set) string|int $paymentRequestId,
        public private(set) string $requestTime,
        private ?string $additional1,
        private ?string $additional2,
        private ?string $additional3,
        private ?string $additional4,
        private ?string $additional5,
    ) {}

    /**
     * @return array<string, mixed>
     */
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
