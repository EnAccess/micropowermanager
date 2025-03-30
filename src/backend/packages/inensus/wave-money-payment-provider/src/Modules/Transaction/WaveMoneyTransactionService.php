<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Ramsey\Uuid\Uuid;

/**
 * @implements IBaseService<WaveMoneyTransaction>
 */
class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService {
    private Meter $meter;
    private Address $address;
    private Transaction $transaction;
    private WaveMoneyTransaction $waveMoneyTransaction;

    public function __construct(
        Meter $meter,
        Address $address,
        Transaction $transaction,
        WaveMoneyTransaction $waveMoneyTransaction,
    ) {
        $this->transaction = $transaction;
        $this->address = $address;
        $this->meter = $meter;
        $this->waveMoneyTransaction = $waveMoneyTransaction;

        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->waveMoneyTransaction
        );
    }

    public function initializeTransactionData(): array {
        $orderId = Uuid::uuid4()->toString(); // need to store somewhere
        $referenceId = Uuid::uuid4()->toString(); // need to store somewhere

        return [
            'order_id' => $orderId,
            'reference_id' => $referenceId,
            'meter_serial' => $this->getMeterSerialNumber(),
            'status' => WaveMoneyTransaction::STATUS_REQUESTED,
            'currency' => 'MMK',
            'customer_id' => $this->getCustomerId(),
            'amount' => $this->getAmount(),
        ];
    }

    public function getByOrderId(string $orderId) {
        return $this->waveMoneyTransaction->newQuery()->where('order_id', '=', $orderId)
            ->firstOrFail();
    }

    public function getByExternalTransactionId(string $externalTransactionId) {
        return $this->waveMoneyTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)
            ->firstOrFail();
    }

    public function getByStatus($status) {
        return $this->waveMoneyTransaction->newQuery()->where('status', '=', $status)
            ->get();
    }

    public function getById(int $id): WaveComTransaction {
        return $this->waveMoneyTransaction->newQuery()->find($id);
    }

    public function update($waveMoneyTransaction, array $waveMoneyTransactionData): WaveMoneyTransaction {
        $waveMoneyTransaction->update($waveMoneyTransactionData);
        $waveMoneyTransaction->fresh();

        return $waveMoneyTransaction;
    }

    public function create($waveMoneyTransactionData): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->create($waveMoneyTransactionData);
    }

    public function delete($waveMoneyTransaction): ?bool {
        return $waveMoneyTransaction->delete();
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->waveMoneyTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->waveMoneyTransaction->newQuery()->get();
    }

    public function getWaveMoneyTransaction() {
        return $this->getPaymentAggregatorTransaction();
    }
}
