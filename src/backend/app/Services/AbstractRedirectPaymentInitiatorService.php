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

    public function getSerialId(): ?string {
        return $this->getMeterSerialNumber();
    }

    /**
     * TODO: This intentionally shadows AbstractPaymentAggregatorTransactionService::validatePaymentOwner,
     * which has different semantics (it also populates customerId/amount/meterSerialNumber used by the
     * inbound imitateTransactionForValidation flow). The two meanings should be split into distinctly
     * named methods once there is test coverage to confirm the inbound provider flow for redirect
     * providers. Kept as-is here to preserve current behaviour.
     */
    public function validatePaymentOwner(string $serialId, float $amount): void {
        if (!$this->validateMeterSerial($serialId)) {
            throw new \Exception('Invalid meter serial number');
        }

        if ($amount <= 0) {
            throw new \Exception('Invalid payment amount');
        }
    }

    public function validateMeterSerial(string $serialId): bool {
        $meter = $this->getMeter()->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        return $meter !== null;
    }

    public function validateSHSSerial(string $serialId): bool {
        $shs = app()->make(SolarHomeSystem::class)
            ->newQuery()
            ->where('serial_number', $serialId)
            ->first();

        return $shs !== null;
    }

    public function validateDeviceSerial(string $serialId, string $deviceType = 'meter'): bool {
        if ($deviceType === 'shs') {
            return $this->validateSHSSerial($serialId);
        }

        return $this->validateMeterSerial($serialId);
    }

    public function getCustomerIdByMeterSerial(string $serialId): ?int {
        $meter = $this->getMeter()->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        if (!$meter) {
            return null;
        }

        return $meter->device->person->id;
    }

    public function getCustomerIdBySHSSerial(string $serialId): ?int {
        $shs = app()->make(SolarHomeSystem::class)
            ->newQuery()
            ->where('serial_number', $serialId)
            ->first();

        if (!$shs) {
            return null;
        }

        $device = $shs->device()->first();
        if (!$device || !$device->person) {
            return null;
        }

        return (int) $device->person->id;
    }

    public function getCustomerPhoneByCustomerId(int $customerId): ?string {
        try {
            $personService = app()->make(PersonService::class);
            $person = $personService->getById($customerId);

            return (string) $person->addresses->first()->phone;
        } catch (\Exception) {
            return null;
        }
    }
}
