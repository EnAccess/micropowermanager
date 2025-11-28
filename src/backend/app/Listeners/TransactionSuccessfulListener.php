<?php

namespace App\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\Transaction\Transaction;
use App\Services\Helpers\TransactionAdapter as HelpersTransactionAdapter;
use App\Services\Interfaces\ITransactionProvider;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = HelpersTransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->sendResult(true, $transaction);
        }
    }

    public function handle(TransactionSuccessfulEvent $event): void {
        $this->onTransactionSuccess($event->transaction);
    }
}
