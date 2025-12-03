<?php

namespace App\Listeners;

use App\Events\TransactionFailedEvent;
use App\Models\Transaction\Transaction;
use App\Providers\Helpers\TransactionAdapter;
use App\Providers\Interfaces\ITransactionProvider;

class TransactionFailedListener {
    public function onTransactionFailed(Transaction $transaction, ?string $message = null): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = TransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->addConflict($message);
            $baseTransaction->sendResult(false, $transaction);
        }
    }

    public function handle(TransactionFailedEvent $event): void {
        $this->onTransactionFailed($event->transaction, $event->message);
    }
}
