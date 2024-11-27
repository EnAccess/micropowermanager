<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use ParseCsv\Csv;

class TransactionService extends AbstractPaymentAggregatorTransactionService {
    public function __construct(private Csv $csv) {}

    public function createTransactionsFromFile(UploadedFile $file): array {
        $this->csv->auto($file);

        $skippedTransactions = [];

        foreach ($this->csv->data as $transactionData) {
            try {
                $this->validateTransaction($transactionData);
                $transaction = new WaveComTransaction();

                $transaction->setTransactionId($transactionData['transaction_id']);
                $transaction->setSender($transactionData['sender']);
                $transaction->setMessage($transactionData['message']);
                $transaction->setAmount((int) $transactionData['amount']);
                $transaction->setStatus(0);
                $transaction->save();
            } catch (\Throwable $t) {
                if ($t instanceof QueryException) {
                    $skippedTransactions[] = $transactionData['transaction_id'].' already imported';
                } elseif ($t instanceof ValidationException) {
                    $skippedTransactions[] = $t->getMessage();
                } else {
                    $skippedTransactions[] = $transactionData['transaction_id'].' unkown reason';
                }
                continue;
            }

            $baseTransaction = new Transaction();
            $baseTransaction->setAmount($transaction->getAmount());
            $baseTransaction->setSender($transaction->getSender());
            $baseTransaction->setMessage($transaction->getMessage());
            $baseTransaction->originalTransaction()->associate($transaction);
            $baseTransaction->setType(Transaction::TYPE_IMPORTED);
            $baseTransaction->save();

            TransactionDataContainer::initialize($baseTransaction);

            ProcessPayment::dispatch($transaction->getId())
                ->allOnConnection('redis')
                ->onQueue(config('services.queues.payment'));
        }

        return $skippedTransactions;
    }

    public function setStatus(WaveComTransaction $transaction, bool $status): void {
        $mappedStatus = $status ? WaveComTransaction::STATUS_SUCCESS : WaveComTransaction::STATUS_CANCELLED;
        $transaction->setStatus($mappedStatus);
        $transaction->save();
    }

    private function validateTransaction(array $transaction) {
        $rules = [
            'transaction_id' => 'required',
            'sender' => 'required',
            'message' => 'required',
            'amount' => 'required',
        ];

        $validator = Validator::make($transaction, $rules);
        $validator->validate();
    }
}
