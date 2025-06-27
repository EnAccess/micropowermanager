<?php

namespace App\Listeners;

use App\Models\Transaction\Transaction;
use MPM\Transaction\Provider\ITransactionProvider;
use MPM\Transaction\Provider\TransactionAdapter;

class TransactionSuccessListener {
    public function onTransactionSuccess(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = TransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->sendResult(true, $transaction);
        }
    }

    public function subscribe(Transaction $transaction): void {
        $this->onTransactionSuccess($transaction);
    }
}
