<?php

namespace App\Listeners;

use App\Models\Transaction\Transaction;
use MPM\Transaction\Provider\ITransactionProvider;
use MPM\Transaction\Provider\TransactionAdapter;

class TransactionFailedListener {
    public function onTransactionFailed(Transaction $transaction, $message = null): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = TransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->addConflict($message);
            $baseTransaction->sendResult(false, $transaction);
        }
    }

    public function handle(Transaction $transaction, $message = null): void {
        $this->onTransactionFailed($transaction, $message);
    }
}
