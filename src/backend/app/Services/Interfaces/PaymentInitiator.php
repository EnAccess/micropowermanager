<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Transaction\Transaction;

/**
 * Contract for payment providers that support MPM-initiated transactions.
 *
 * This is the flow where MPM requests a payment from the customer, as opposed to
 * payments the customer initiates externally (e.g. an unsolicited mobile-money
 * transfer that MPM receives as an inbound notification). A provider implements
 * this interface only if MPM can initiate a transaction with it.
 *
 * Requests to initiate a transaction can originate anywhere in MPM,
 * e.g. the "request payment" button in the Sold Appliance view, the public payment
 * website, or the agent app.
 *
 * Initiating a payment creates a provider-specific record (e.g. PaystackTransaction,
 * CashTransaction) plus an associated Transaction, both in "requested" status. The
 * returned provider_data then varies by provider:
 * - redirect-based (e.g. Paystack): a redirect_url the client follows to complete payment.
 * - immediate (e.g. cash): empty; the transaction is ready to process right away.
 * - mobile money: empty; the customer confirms a USSD push with their mobile-money PIN.
 *
 * Payment validation (device ownership, minimum purchase amounts) is handled
 * separately by ITransactionProvider::validateRequest() and
 * AbstractPaymentAggregatorTransactionService::validatePaymentOwner().
 */
interface PaymentInitiator {
    /**
     * `process_immediately` tells the caller whether the payment is already confirmed and should be
     * processed now (e.g. cash, or a synchronous mobile-money push that returns the final outcome).
     * Redirect-based providers return false: their payment is confirmed later via a provider callback,
     * which dispatches the processing itself.
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
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
