<?php

namespace App\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\Transaction\Transaction;
use MPM\Transaction\Provider\ITransactionProvider;
use MPM\Transaction\Provider\TransactionAdapter;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = TransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->sendResult(true, $transaction);
        }
    }

    public function handle(TransactionSuccessfulEvent $event): void {
        $this->onTransactionSuccess($event->transaction);
    }
}
