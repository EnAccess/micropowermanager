<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SolarHomeSystem;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\PaymentInitiator;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Shared behaviour for redirect-based payment providers (e.g. Paystack, PesaPal)
 * where MPM creates a provider transaction and hands the customer a redirect URL
 * to complete payment on the provider's hosted page.
 *
 * Providers only differ in their currency source, status constants and the API
 * call plus the shape of the redirect data they return, which are supplied by the
 * abstract hooks below.
 *
 * @template T of BasePaymentProviderTransaction
 *
 * @extends AbstractPaymentAggregatorTransactionService<T>
 */
abstract class AbstractRedirectPaymentInitiatorService extends AbstractPaymentAggregatorTransactionService implements PaymentInitiator {
    /**
     * Currency to record on a newly created provider transaction.
     */
    abstract protected function resolveCurrency(): string;

    /**
     * Status value representing a freshly requested provider transaction.
     */
    abstract protected function requestedStatus(): int;

    /**
     * Submit the created provider transaction to the remote gateway.
     *
     * Must throw when the gateway rejects the request, otherwise return the
     * provider-specific redirect data exposed to the caller as provider_data.
     *
     * @return array<string, mixed>
     */
    abstract protected function submitToProvider(BasePaymentProviderTransaction $transaction, string $sender): array;

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
    ): array {
        $deviceType = null;
        if ($serialId !== null) {
            $device = app(DeviceService::class)->getBySerialNumber($serialId);
            $deviceType = $device?->device_type;
        }

        try {
            // A failed gateway initiation must not leave orphaned transaction rows behind.
            DB::connection('tenant')->beginTransaction();

            $aggregatorTransaction = $this->getPaymentAggregatorTransaction()->newQuery()->create([
                'amount' => $amount,
                'currency' => $this->resolveCurrency(),
                'order_id' => Uuid::uuid4()->toString(),
                'reference_id' => Uuid::uuid4()->toString(),
                'status' => $this->requestedStatus(),
                'customer_id' => $customerId,
                'serial_id' => $serialId,
                'device_type' => $deviceType,
                'metadata' => ['customer_id' => $customerId, 'serial_id' => $serialId, 'transaction_type' => $type],
            ]);

            /** @var Transaction $transaction */
            $transaction = $aggregatorTransaction->transaction()->create([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $message,
                'type' => $type,
            ]);

            $providerData = $this->submitToProvider($aggregatorTransaction, $sender);

            DB::connection('tenant')->commit();

            return [
                'transaction' => $transaction,
                'provider_data' => $providerData,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }
}
