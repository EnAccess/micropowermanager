<?php

namespace App\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\Transaction\Transaction;
use App\Providers\Helpers\TransactionAdapter;
use App\Providers\Interfaces\ITransactionProvider;

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
