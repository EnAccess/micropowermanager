<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Transaction;
namespace Inensus\PaystackPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Ramsey\Uuid\Uuid;

/**
 * @implements IBaseService<PaystackTransaction>
 */
class PaystackTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService {
    private Meter $meter;
    private Address $address;
    private Transaction $transaction;
    private PaystackTransaction $paystackTransaction;

    public function __construct(
        Meter $meter,
        Address $address,
        Transaction $transaction,
        PaystackTransaction $paystackTransaction,
    ) {
        $this->transaction = $transaction;
        $this->address = $address;
        $this->meter = $meter;
        $this->paystackTransaction = $paystackTransaction;

        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->paystackTransaction
        );
    }

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
        $paystackTransaction->transaction()->create([
            'amount' => $paystackTransaction->getAmount(),
            'sender' => $paystackTransaction->getCustomerId(),
            'message' => $paystackTransaction->getSerialId(),
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
        ProcessPayment::dispatch($companyId, $id);
        $transaction->setStatus(PaystackTransaction::STATUS_SUCCESS);
        $transaction->save();
    }
}
