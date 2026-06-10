<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Transaction\Transaction;

/**
 * Contract for payment providers that support MPM-initiated transactions.
 *
 * This covers the flow where MPM requests a payment from the customer, as
 * opposed to payments the customer initiates externally (e.g. an unsolicited
 * mobile-money transfer that MPM receives as an inbound notification).
 *
 * Such MPM-initiated requests can originate from anywhere inside the system,
 * for example
 * - "request payment" button in Sold Appliance view
 * - public-facing payment website,
 * - the agent app
 *
 * A provider only implements this interface if MPM is able to initiate a transaction
 * with it.
 *
 * As part of the initiation a provider-specific record (e.g. PaystackTransaction,
 * CashTransaction) and an associated Transaction record should be created.
 * The initial records should be in "requested" status.
 *
 * Then, the payment provider does it's thing. For example
 * - For redirect-based providers (e.g. Paystack), the returned provider_data
 *   contains a redirect_url the client must follow to complete payment.
 * - For immediate providers (e.g. cash), provider_data is empty and the
 *   transaction is ready for processing right away.
 * - Mobile money providers send USSD push to the customer which they have
 *   to confirm with their mobile money pin
 *
 * Payment validation (device ownership, minimum purchase amounts) is handled
 * separately by ITransactionProvider::validateRequest() and
 * AbstractPaymentAggregatorTransactionService::validatePaymentOwner().
 */
interface PaymentInitiator {
    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>}
     */
    public function initiatePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array;
}
