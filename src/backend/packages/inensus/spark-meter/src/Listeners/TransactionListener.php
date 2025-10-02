<?php

namespace Inensus\SparkMeter\Listeners;

use App\Models\Transaction\Transaction;
use Inensus\SparkMeter\Models\SmTransaction;
use Inensus\SparkMeter\Services\TransactionService;

class TransactionListener {
    public function __construct(private TransactionService $transactionService, private SmTransaction $smTransaction) {}

    /**
     * Sets the in_use to true.
     */
    public function onTransactionSuccess(Transaction $transaction): void {
        $smTransaction = $this->smTransaction->newQuery()->where('mpm_transaction_id', $transaction->id)->first();
        if ($smTransaction) {
            $this->transactionService->updateTransactionStatus($smTransaction);
        }
    }

    public function handle(Transaction $transaction): void {
        $this->onTransactionSuccess($transaction);
    }
}
