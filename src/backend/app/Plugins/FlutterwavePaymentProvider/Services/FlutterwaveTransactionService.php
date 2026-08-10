<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Services;

use App\Enums\DeviceType;
use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\FlutterwaveApiService;
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
 * @extends AbstractPaymentAggregatorTransactionService<FlutterwaveTransaction>
 *
 * @implements IBaseService<FlutterwaveTransaction>
 */
class FlutterwaveTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService, PaymentInitiator {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private FlutterwaveTransaction $flutterwaveTransaction,
        private FlutterwaveApiService $flutterwaveApiService,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->flutterwaveTransaction
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
            'status' => FlutterwaveTransaction::STATUS_REQUESTED,
            'currency' => 'NGN',
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'metadata' => [
                'serial_id' => $this->meterSerialNumber,
                'customer_id' => $this->customerId,
            ],
        ];
    }

    public function getByOrderId(string $orderId): ?FlutterwaveTransaction {
        return $this->flutterwaveTransaction->newQuery()->where('order_id', '=', $orderId)->first();
    }

    public function getByExternalTransactionId(string $externalTransactionId): ?FlutterwaveTransaction {
        return $this->flutterwaveTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)->first();
    }

    public function getByFlutterwaveReference(string $reference): ?FlutterwaveTransaction {
        return $this->flutterwaveTransaction->newQuery()->where('flutterwave_reference', '=', $reference)->first();
    }

    /**
     * @return Collection<int, FlutterwaveTransaction>
     */
    public function getByStatus(int $status): Collection {
        return $this->flutterwaveTransaction->newQuery()->where('status', '=', $status)->get();
    }

    public function getById(int $id): ?FlutterwaveTransaction {
        return $this->flutterwaveTransaction->newQuery()->find($id);
    }

    /**
     * @return Collection<int, FlutterwaveTransaction>|LengthAwarePaginator<int, FlutterwaveTransaction>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->flutterwaveTransaction->newQuery()->latest();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    public function create(array $flutterwaveTransactionData): FlutterwaveTransaction {
        try {
            // Run on the tenant connection so a failure rolls back the right database.
            DB::connection('tenant')->beginTransaction();

            /** @var FlutterwaveTransaction $flutterwaveTransaction */
            $flutterwaveTransaction = $this->flutterwaveTransaction->newQuery()->create($flutterwaveTransactionData);

            // Get customer's phone number for sender field
            $customerPhone = $this->getCustomerPhoneByCustomerId($flutterwaveTransaction->customer_id);
            $sender = $customerPhone ?: '';

            $flutterwaveTransaction->transaction()->create([
                'amount' => $flutterwaveTransaction->amount,
                'sender' => $sender,
                'message' => $flutterwaveTransaction->serial_id,
                'type' => 'energy',
            ]);

            DB::connection('tenant')->commit();

            return $flutterwaveTransaction;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    public function processSuccessfulPayment(int $companyId, FlutterwaveTransaction $transaction): void {
        $id = $transaction->transaction->id;
        dispatch(new ProcessPayment($companyId, $id));
        $transaction->status = FlutterwaveTransaction::STATUS_SUCCESS;
        $transaction->save();
    }

    public function processFailedPayment(FlutterwaveTransaction $transaction): void {
        $transaction->status = FlutterwaveTransaction::STATUS_FAILED;
        $transaction->save();

        $relatedTransaction = $transaction->transaction;
        if ($relatedTransaction) {
            $relatedTransaction->update(['status' => FlutterwaveTransaction::STATUS_FAILED]);
        }
    }

    /**
     * Create a FlutterwaveTransaction + Transaction and initialize via the Flutterwave API.
     * The caller supplies message and type, keeping routing knowledge outside this service.
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
    ): array {
        $deviceType = null;
        if ($serialId !== null) {
            $device = resolve(DeviceService::class)->getBySerialNumber($serialId);
            $deviceType = $device?->device_type;
        }

        try {
            // A failed Flutterwave initialization must not leave orphaned transaction rows behind.
            DB::connection('tenant')->beginTransaction();

            $flutterwaveTxn = $this->flutterwaveTransaction->newQuery()->create([
                'amount' => $amount,
                'currency' => config('flutterwave-payment-provider.currency.default', 'NGN'),
                'order_id' => Uuid::uuid4()->toString(),
                'reference_id' => Uuid::uuid4()->toString(),
                'status' => FlutterwaveTransaction::STATUS_REQUESTED,
                'customer_id' => $customerId,
                'serial_id' => $serialId,
                'device_type' => $deviceType,
                'metadata' => ['customer_id' => $customerId, 'serial_id' => $serialId, 'transaction_type' => $type],
            ]);

            /** @var Transaction $transaction */
            $transaction = $flutterwaveTxn->transaction()->create([
                'amount' => $amount,
                'sender' => $sender,
                'message' => $message,
                'type' => $type,
            ]);

            $result = $this->flutterwaveApiService->initializeTransaction($flutterwaveTxn);
            if ($result['error']) {
                throw new \RuntimeException('Flutterwave initialization failed: '.$result['error']);
            }

            $flutterwaveTxn->flutterwave_reference = $flutterwaveTxn->reference_id;
            $flutterwaveTxn->save();

            DB::connection('tenant')->commit();

            return [
                'transaction' => $transaction,
                'provider_data' => [
                    'redirect_url' => $result['redirectionUrl'],
                    'reference' => $result['reference'],
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
    public function createPublicTransaction(array $transactionData): FlutterwaveTransaction {
        // Create Flutterwave transaction without customer association
        $transactionData['status'] = FlutterwaveTransaction::STATUS_REQUESTED;
        $transactionData['metadata'] = [
            'serial_id' => $transactionData['serial_id'],
            'customer_id' => $transactionData['customer_id'],
            'public_payment' => true,
        ];

        // Add agent_id to metadata if provided
        if (isset($transactionData['agent_id']) && $transactionData['agent_id']) {
            $transactionData['metadata']['agent_id'] = $transactionData['agent_id'];
        }

        return $this->flutterwaveTransaction->newQuery()->create($transactionData);
    }

    public function validateMeterSerial(string $serialId): bool {
        // Check if meter exists and is active
        $meter = $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        return $meter !== null;
    }

    public function validateSHSSerial(string $serialId): bool {
        // Check if SHS exists
        $shs = app()->make(SolarHomeSystem::class)
            ->newQuery()
            ->where('serial_number', $serialId)
            ->first();

        return $shs !== null;
    }

    public function validateDeviceSerial(string $serialId, string $deviceType = DeviceType::Meter->value): bool {
        if ($deviceType === DeviceType::SolarHomeSystem->value) {
            return $this->validateSHSSerial($serialId);
        }

        return $this->validateMeterSerial($serialId);
    }

    public function validatePaymentOwner(string $serialId, float $amount): void {
        // For public payments, we only validate that the meter exists
        if (!$this->validateMeterSerial($serialId)) {
            throw new \Exception('Invalid meter serial number');
        }

        // Additional validation can be added here (e.g., amount limits)
        if ($amount <= 0) {
            throw new \Exception('Invalid payment amount');
        }
    }

    public function getCustomerIdByMeterSerial(string $serialId): ?int {
        // Find the meter by serial number and get the associated customer ID
        $meter = $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        if (!$meter) {
            return null;
        }

        // Return the customer ID associated with the meter
        $person = $meter->device->person->id;

        return $person;
    }

    public function getCustomerIdBySHSSerial(string $serialId): ?int {
        // Find SHS by serial number and resolve owning person via device relationship
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
        // Get the customer's phone number by customer ID
        try {
            $personService = app()->make(PersonService::class);
            $person = $personService->getById($customerId);

            return (string) $person->addresses->first()->phone;
        } catch (\Exception) {
            return null;
        }
    }
}
