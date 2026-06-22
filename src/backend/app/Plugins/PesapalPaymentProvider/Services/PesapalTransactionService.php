<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\GetTransactionStatusResource;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\DeviceService;
use App\Services\Interfaces\IBaseService;
use App\Services\Interfaces\PaymentInitiator;
use App\Services\PersonService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractPaymentAggregatorTransactionService<PesapalTransaction>
 *
 * @implements IBaseService<PesapalTransaction>
 */
class PesapalTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService, PaymentInitiator {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private PesapalTransaction $pesapalTransaction,
        private PesapalApiService $pesapalApiService,
        private PesapalCredentialService $credentialService,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->pesapalTransaction
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function initializeTransactionData(): array {
        $orderId = Uuid::uuid4()->toString();
        $referenceId = Uuid::uuid4()->toString();

        return [
            'order_id' => $orderId,
            'reference_id' => $referenceId,
            'serial_id' => $this->meterSerialNumber,
            'status' => PesapalTransaction::STATUS_REQUESTED,
            'currency' => $this->credentialService->getCredentials()->currency,
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'metadata' => [
                'serial_id' => $this->meterSerialNumber,
                'customer_id' => $this->customerId,
            ],
        ];
    }

    public function getByOrderId(string $orderId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('order_id', '=', $orderId)->first();
    }

    public function getByExternalTransactionId(string $externalTransactionId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)->first();
    }

    public function getByOrderTrackingId(string $orderTrackingId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('order_tracking_id', '=', $orderTrackingId)->first();
    }

    public function getByMerchantReference(string $merchantReference): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('merchant_reference', '=', $merchantReference)->first();
    }

    public function getByReferenceId(string $referenceId): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->where('reference_id', '=', $referenceId)->first();
    }

    /**
     * @return Collection<int, PesapalTransaction>
     */
    public function getByStatus(int $status): Collection {
        return $this->pesapalTransaction->newQuery()->where('status', '=', $status)->get();
    }

    public function getById(int $id): ?PesapalTransaction {
        return $this->pesapalTransaction->newQuery()->find($id);
    }

    public function update($pesapalTransaction, array $pesapalTransactionData): PesapalTransaction {
        $pesapalTransaction->update($pesapalTransactionData);
        $pesapalTransaction->fresh();

        return $pesapalTransaction;
    }

    public function create(array $pesapalTransactionData): PesapalTransaction {
        try {
            DB::connection('tenant')->beginTransaction();

            /** @var PesapalTransaction $pesapalTransaction */
            $pesapalTransaction = $this->pesapalTransaction->newQuery()->create($pesapalTransactionData);

            $customerPhone = $this->getCustomerPhoneByCustomerId($pesapalTransaction->customer_id);
            $sender = $customerPhone ?: '';

            $pesapalTransaction->transaction()->create([
                'amount' => $pesapalTransaction->amount,
                'sender' => $sender,
                'message' => $pesapalTransaction->serial_id,
                'type' => 'energy',
            ]);

            DB::connection('tenant')->commit();

            return $pesapalTransaction;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    public function delete($pesapalTransaction): ?bool {
        return $pesapalTransaction->delete();
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->pesapalTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->pesapalTransaction->newQuery()->get();
    }

    public function processSuccessfulPayment(int $companyId, PesapalTransaction $transaction): void {
        $id = $transaction->transaction->id;
        dispatch(new ProcessPayment($companyId, $id));
        $transaction->status = PesapalTransaction::STATUS_SUCCESS;
        $transaction->save();
    }

    public function processFailedPayment(PesapalTransaction $transaction): void {
        $transaction->status = PesapalTransaction::STATUS_FAILED;
        $transaction->save();
    }

    /**
     * Fetches the authoritative status from PesaPal and persists it on the
     * transaction. The same code path serves IPN callbacks and operator-driven
     * verify calls so a transaction is never left stuck in REQUESTED just
     * because PesaPal didn't fire an IPN (e.g. customer aborted on the gateway).
     *
     * @return array{status_code: ?int, status_description: string, amount: float, currency: string, payment_method: string, confirmation_code: string, merchant_reference: string, error: ?string}
     */
    public function syncStatusFromApi(PesapalTransaction $transaction, int $companyId): array {
        $status = $this->pesapalApiService->getTransactionStatus($transaction->order_tracking_id);
        if ($status['error'] !== null) {
            return $status;
        }

        if (!empty($status['confirmation_code'])) {
            $transaction->external_transaction_id = $status['confirmation_code'];
            $transaction->save();
        }

        $this->applyStatusCode($transaction, $status['status_code'], $companyId);

        return $status;
    }

    private function applyStatusCode(PesapalTransaction $transaction, ?int $statusCode, int $companyId): void {
        switch ($statusCode) {
            case GetTransactionStatusResource::STATUS_COMPLETED:
                $this->processSuccessfulPayment($companyId, $transaction);
                break;
            case GetTransactionStatusResource::STATUS_FAILED:
            case GetTransactionStatusResource::STATUS_REVERSED:
                $this->processFailedPayment($transaction);
                break;
            case GetTransactionStatusResource::STATUS_INVALID:
                $transaction->status = PesapalTransaction::STATUS_ABANDONED;
                $transaction->save();
                break;
        }
    }

    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
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
            $device = resolve(DeviceService::class)->getBySerialNumber($serialId);
            $deviceType = $device?->device_type;
        }

        $credential = $this->credentialService->getCredentials();

        try {
            DB::connection('tenant')->beginTransaction();

            $pesapalTxn = $this->pesapalTransaction->newQuery()->create([
                'amount' => $amount,
                'currency' => $credential->currency ?: config('pesapal-payment-provider.currency.default', 'KES'),
                'order_id' => Uuid::uuid4()->toString(),
                'reference_id' => Uuid::uuid4()->toString(),
                'status' => PesapalTransaction::STATUS_REQUESTED,
                'customer_id' => $customerId,
                'serial_id' => $serialId,
                'device_type' => $deviceType,
                'metadata' => ['customer_id' => $customerId, 'serial_id' => $serialId, 'transaction_type' => $type],
            ]);

            /** @var Transaction $transaction */
            $transaction = $pesapalTxn->transaction()->create([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $message,
                'type' => $type,
            ]);

            $result = $this->pesapalApiService->submitOrder($pesapalTxn, $sender);
            if ($result['error']) {
                throw new \RuntimeException('Pesapal initialization failed: '.$result['error']);
            }

            DB::connection('tenant')->commit();

            return [
                'transaction' => $transaction,
                'provider_data' => [
                    'redirect_url' => $result['redirect_url'],
                    'order_tracking_id' => $result['order_tracking_id'],
                    'merchant_reference' => $result['merchant_reference'],
                ],
                'process_immediately' => false,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $transactionData
     */
    public function createPublicTransaction(array $transactionData): PesapalTransaction {
        $transactionData['status'] = PesapalTransaction::STATUS_REQUESTED;
        $transactionData['metadata'] = [
            'serial_id' => $transactionData['serial_id'],
            'customer_id' => $transactionData['customer_id'],
            'public_payment' => true,
        ];

        if (isset($transactionData['agent_id']) && $transactionData['agent_id']) {
            $transactionData['metadata']['agent_id'] = $transactionData['agent_id'];
        }

        return $this->pesapalTransaction->newQuery()->create($transactionData);
    }

    public function validateMeterSerial(string $serialId): bool {
        $meter = $this->meter->newQuery()
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
        if ($deviceType === 'solar_home_system') {
            return $this->validateSHSSerial($serialId);
        }

        return $this->validateMeterSerial($serialId);
    }

    public function validatePaymentOwner(string $serialId, float $amount): void {
        if (!$this->validateMeterSerial($serialId)) {
            throw new \Exception('Invalid meter serial number');
        }

        if ($amount <= 0) {
            throw new \Exception('Invalid payment amount');
        }
    }

    public function getCustomerIdByMeterSerial(string $serialId): ?int {
        $meter = $this->meter->newQuery()
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
