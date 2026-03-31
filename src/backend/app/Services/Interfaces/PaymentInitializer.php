<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Transaction\Transaction;

/**
 * Contract for payment provider initialization.
 *
 * "Initialize" means creating a provider-specific record (e.g. PaystackTransaction,
 * CashTransaction) and an associated Transaction record in "requested" status.
 *
 * For redirect-based providers (e.g. Paystack), the returned provider_data
 * contains a redirect_url the client must follow to complete payment.
 * For immediate providers (e.g. cash), provider_data is empty and the
 * transaction is ready for processing right away.
 *
 * Provider validation (device ownership, minimum purchase amounts) is handled
 * separately by ITransactionProvider::validateRequest() and
 * AbstractPaymentAggregatorTransactionService::validatePaymentOwner().
 */
interface PaymentInitializer {
    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>}
     */
    public function initializePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array;
}
