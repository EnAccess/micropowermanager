<?php

namespace Inensus\SwiftaPaymentProvider\Services;


use App\Misc\TransactionDataContainer;
use App\Models\Address\Address;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\IBaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction\TransactionConflicts;

class SwiftaTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService
{

    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private MeterParameter $meterParameter,
        private SwiftaTransaction $swiftaTransaction,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->meterParameter,
            $this->swiftaTransaction
        );
    }

    public function initializeTransactionData($data): array
    {
        return [
            'amount' => $data['amount'],
            'cipher' => $data['cipher'],
            'status' => SwiftaTransaction::STATUS_REQUESTED,
            'timestamp' => $data['timestamp'],
        ];
    }

    public function setRequestedTransactionsStatusFailed()
    {
        $this->swiftaTransaction->newQuery()->where('status', SwiftaTransaction::STATUS_REQUESTED)->get()->each(function ($transaction) {
            $transaction->update([
                'status' => SwiftaTransaction::STATUS_FAILED
            ]);
            $message = "The transaction that stayed as Unprocessed more than 24 hours, updated to canceled.";
            $conflict = new TransactionConflicts();
            $conflict->state = $message;
            $conflict->transaction()->associate($transaction);
            $conflict->save();
            Log::debug($message . " Transaction Id : {$transaction->id}");
        });
    }

    public function getSwiftaTransaction()
    {
        return $this->getPaymentAggregatorTransaction();
    }

    public function getTransactionById($transactionId)
    {
        try {

            return $this->transaction->newQuery()->findOrFail($transactionId);
        } catch (ModelNotFoundException $exception) {
            throw  new \Exception('transaction_id validation field.');
        }

    }

    public function checkAmountIsSame($amount, $transaction)
    {
        if ($amount != (int)$transaction->amount) {
            throw new \Exception('amount validation field.');
        }
    }

    public function getById($id)
    {
        return $this->swiftaTransaction->newQuery()->find($id);
    }


    public function update($swiftaTransaction, $swiftaTransactionData)
    {
        $swiftaTransaction->update($swiftaTransactionData);
        $swiftaTransaction->fresh();

        return $swiftaTransaction;
    }

    public function create($swiftaTransactionData)
    {
        return $this->swiftaTransaction->newQuery()->create($swiftaTransactionData);
    }

    public function delete($swiftaTransaction)
    {
        return $swiftaTransaction->delete();
    }

    public function getAll($limit = null)
    {
        $query = $this->swiftaTransaction->newQuery();

        if ($limit) {
            return $query->paginate($limit);
        }

        return $this->swiftaTransaction->newQuery()->get();
    }
}

