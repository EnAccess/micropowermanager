<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractPaymentAggregatorTransactionService<WaveMoneyTransaction>
 *
 * @implements IBaseService<WaveMoneyTransaction>
 */
class WaveMoneyTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        public private(set) WaveMoneyTransaction $waveMoneyTransaction,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->waveMoneyTransaction
        );
    }

    /**
     * @return array{
     *     order_id: string,
     *     reference_id: string,
     *     meter_serial: string,
     *     status: int,
     *     currency: string,
     *     customer_id: int,
     *     amount: float|int
     * }
     */
    public function initializeTransactionData(): array {
        $orderId = Uuid::uuid4()->toString(); // need to store somewhere
        $referenceId = Uuid::uuid4()->toString(); // need to store somewhere

        return [
            'order_id' => $orderId,
            'reference_id' => $referenceId,
            'meter_serial' => $this->meterSerialNumber,
            'status' => WaveMoneyTransaction::STATUS_REQUESTED,
            'currency' => 'MMK',
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
        ];
    }

    public function getByOrderId(string $orderId): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->where('order_id', '=', $orderId)
            ->firstOrFail();
    }

    /**
     * Apply a Wave Money bill-collection callback. Wave Money redelivers callbacks, so the
     * settlement itself is guarded by the base service; this only decides which way to go and
     * records the attempt.
     */
    public function applyCallback(
        WaveMoneyTransaction $transaction,
        TransactionCallbackData $callbackData,
        int $companyId,
    ): void {
        // Every delivery counts, including redeliveries and the ones we go on to refuse.
        // Incremented in SQL so concurrent redeliveries cannot overwrite each other's count.
        $transaction->increment('attempts');

        if ($callbackData->mapTransactionStatus($callbackData->status) === TransactionCallbackData::STATUS_FAILURE) {
            $this->processFailedPayment($transaction);

            return;
        }

        $mismatch = $this->paymentMismatch(
            'Wave Money',
            [
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
            ],
            [
                'amount' => $callbackData->amount,
                'currency' => $callbackData->currency,
            ],
        );
        if ($mismatch !== null) {
            $this->recordPaymentConflict($transaction, $mismatch, ['order_id' => $transaction->order_id]);

            return;
        }

        if ($callbackData->transactionId !== null) {
            $transaction->external_transaction_id = $callbackData->transactionId;
        }

        $this->processSuccessfulPayment($companyId, $transaction);
    }

    public function getByExternalTransactionId(string $externalTransactionId): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->where('external_transaction_id', '=', $externalTransactionId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, WaveMoneyTransaction>
     */
    public function getByStatus(int $status): Collection {
        return $this->waveMoneyTransaction->newQuery()->where('status', '=', $status)
            ->get();
    }

    public function getById(int $id): WaveMoneyTransaction {
        return $this->waveMoneyTransaction->newQuery()->findOrFail($id);
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
}
