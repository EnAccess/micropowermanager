<?php

namespace App\Services\ImportServices;

final readonly class TransactionImportItem {
    /**
     * @param array<string, mixed>|null $originalTransaction
     */
    public function __construct(
        public string $deviceId,
        public float $amount,
        public ?string $customer,
        public ?string $transactionType,
        public ?array $originalTransaction,
        public ?string $sentDate,
    ) {}
}
