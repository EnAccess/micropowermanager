<?php

namespace App\DTO;

/**
 * Trimmed response for a payment registered through the external transactions API - only
 * what the caller needs to confirm the payment and know the resulting balance, not the full
 * AppliancePerson/Person models.
 */
class ThirdPartyPaymentResult {
    public function __construct(
        public int $transactionId,
        public string $serial,
        public float $amount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'transaction_id' => $this->transactionId,
            'serial' => $this->serial,
            'amount' => $this->amount,
        ];
    }
}
