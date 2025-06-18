<?php

namespace App\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Events\Dispatcher;
use MPM\Transaction\Provider\ITransactionProvider;
use MPM\Transaction\Provider\TransactionAdapter;

class TransactionSubscriber {
    public function onTransactionSaved(ITransactionProvider $transactionProvider): void {
        // echos the confirmation output
        $transactionProvider->confirm();
    }

    public function onTransactionFailed(Transaction $transaction, $message = null): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = TransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->addConflict($message);
            $baseTransaction->sendResult(false, $transaction);
        }
    }

    public function onTransactionSuccess(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof ITransactionProvider) {
            $baseTransaction = TransactionAdapter::getTransaction($originalTransaction);
            $baseTransaction->sendResult(true, $transaction);
        }
    }

    public function subscribe(Dispatcher $events): void {
        $events->listen(
            'transaction.saved',
            [TransactionSubscriber::class, 'onTransactionSaved']
        );
        $events->listen(
            'transaction.successful',
            [TransactionSubscriber::class, 'onTransactionSuccess']
        );
        $events->listen(
            'transaction.failed',
            [TransactionSubscriber::class, 'onTransactionFailed']
        );
    }
}
