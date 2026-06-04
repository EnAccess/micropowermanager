<?php

namespace App\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Providers\Helpers\TransactionAdapter;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof BasePaymentProviderTransaction) {
            TransactionAdapter::getTransaction($originalTransaction)?->sendResult(true, $transaction);
        }
    }

    public function handle(TransactionSuccessfulEvent $event): void {
        $this->onTransactionSuccess($event->transaction);
    }
}
