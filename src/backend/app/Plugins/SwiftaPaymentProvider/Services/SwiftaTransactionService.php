<?php

namespace App\Plugins\SwiftaPaymentProvider\Services;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * @extends AbstractPaymentAggregatorTransactionService<SwiftaTransaction>
 */
class SwiftaTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private SwiftaTransaction $swiftaTransaction,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->swiftaTransaction
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{amount: mixed, cipher: mixed, status: int, timestamp:mixed}
     */
    public function initializeTransactionData(array $data): array {
        return [
            'amount' => $data['amount'],
            'cipher' => $data['cipher'],
            'status' => SwiftaTransaction::STATUS_REQUESTED,
            'timestamp' => $data['timestamp'],
        ];
    }

    public function setRequestedTransactionsStatusFailed(): void {
        $this->swiftaTransaction->newQuery()->where('status', SwiftaTransaction::STATUS_REQUESTED)->get()->each(function ($transaction) {
            $transaction->update([
                'status' => SwiftaTransaction::STATUS_FAILED,
            ]);
            $message = 'The transaction that stayed as Unprocessed more than 24 hours, updated to canceled.';
            $conflict = new TransactionConflicts();
            $conflict->state = $message;
            $conflict->transaction()->associate($transaction);
            $conflict->save();
            Log::debug($message." Transaction Id : {$transaction->id}");
        });
    }

    public function getSwiftaTransaction(): SwiftaTransaction {
        return $this->getPaymentAggregatorTransaction();
    }

    public function getTransactionById(int $transactionId): Transaction {
        try {
            return $this->transaction->newQuery()->findOrFail($transactionId);
        } catch (ModelNotFoundException $exception) {
            throw new \Exception('transaction_id validation field.', $exception->getCode(), $exception);
        }
    }

    public function checkAmountIsSame(int $amount, Transaction $transaction): void {
        if ($amount !== (int) $transaction->amount) {
            throw new \Exception('amount validation field.');
        }
    }

    public function getById(?int $id): SwiftaTransaction {
        return $this->swiftaTransaction->newQuery()->find($id);
    }
}
