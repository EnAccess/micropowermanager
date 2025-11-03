<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Transaction;

namespace Inensus\PaystackPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;
use App\Services\PersonService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Ramsey\Uuid\Uuid;

/**
 * @implements IBaseService<PaystackTransaction>
 */
class PaystackTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private PaystackTransaction $paystackTransaction,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->paystackTransaction
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
            'serial_id' => $this->getSerialId(),
            'status' => PaystackTransaction::STATUS_REQUESTED,
            'currency' => 'NGN',
            'customer_id' => $this->getCustomerId(),
            'amount' => $this->getAmount(),
            'metadata' => [
                'serial_id' => $this->getSerialId(),
                'customer_id' => $this->getCustomerId(),
            ],
        ];
    }

    public function getByOrderId(string $orderId): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->where('order_id', '=', $orderId)->first();
    }

    public function getByExternalTransactionId(string $externalTransactionId): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)->first();
    }

    public function getByPaystackReference(string $reference): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->where('paystack_reference', '=', $reference)->first();
    }

    /**
     * @return Collection<int, PaystackTransaction>
     */
    public function getByStatus(int $status): Collection {
        return $this->paystackTransaction->newQuery()->where('status', '=', $status)->get();
    }

    public function getById(int $id): ?PaystackTransaction {
        return $this->paystackTransaction->newQuery()->find($id);
    }

    public function update($paystackTransaction, array $paystackTransactionData): PaystackTransaction {
        $paystackTransaction->update($paystackTransactionData);
        $paystackTransaction->fresh();

        return $paystackTransaction;
    }

    public function create(array $paystackTransactionData): PaystackTransaction {
        /** @var PaystackTransaction $paystackTransaction */
        $paystackTransaction = $this->paystackTransaction->newQuery()->create($paystackTransactionData);

        // Get customer's phone number for sender field
        $customerPhone = $this->getCustomerPhoneByCustomerId($paystackTransaction->getCustomerId());
        $sender = $customerPhone ?: '';

        $paystackTransaction->transaction()->create([
            'amount' => $paystackTransaction->getAmount(),
            'sender' => $sender,
            'message' => $paystackTransaction->getDeviceSerial(),
            'type' => 'energy',
        ]);

        return $paystackTransaction;
    }

    public function delete($paystackTransaction): ?bool {
        return $paystackTransaction->delete();
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->paystackTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->paystackTransaction->newQuery()->get();
    }

    public function getPaystackTransaction(): PaystackTransaction {
        return $this->getPaymentAggregatorTransaction();
    }

    public function getSerialId(): ?string {
        return $this->getMeterSerialNumber();
    }

    public function processSuccessfulPayment(int $companyId, PaystackTransaction $transaction): void {
        $id = $transaction->transaction->id;
        dispatch(new ProcessPayment($companyId, $id));
        $transaction->setStatus(PaystackTransaction::STATUS_SUCCESS);
        $transaction->save();
    }

    /**
     * @param array<string, mixed> $transactionData
     */
    public function createPublicTransaction(array $transactionData): PaystackTransaction {
        // Create Paystack transaction without customer association
        $transactionData['status'] = PaystackTransaction::STATUS_REQUESTED;
        $transactionData['metadata'] = [
            'serial_id' => $transactionData['serial_id'],
            'customer_id' => $transactionData['customer_id'],
            'public_payment' => true,
        ];

        // Add agent_id to metadata if provided
        if (isset($transactionData['agent_id']) && $transactionData['agent_id']) {
            $transactionData['metadata']['agent_id'] = $transactionData['agent_id'];
        }

        return $this->paystackTransaction->newQuery()->create($transactionData);
    }

    public function validateMeterSerial(string $serialId): bool {
        // Check if meter exists and is active
        $meter = $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();

        return $meter !== null;
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

            return $person->addresses->first()->phone;
        } catch (\Exception) {
            return null;
        }
    }
}
