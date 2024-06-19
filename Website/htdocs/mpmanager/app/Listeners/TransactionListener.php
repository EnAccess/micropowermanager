<?php

namespace App\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Events\Dispatcher;
use MPM\Transaction\Provider\ITransactionProvider;
use MPM\Transaction\Provider\TransactionAdapter;

class TransactionListener
{
    public function onTransactionSaved(ITransactionProvider $transactionProvider): void
    {
        // echos the confirmation output
        $transactionProvider->confirm();
    }

    public function onTransactionFailed(Transaction $transaction, $message = null): void
    {
        $baseTransaction = TransactionAdapter::getTransaction($transaction->originalTransaction()->first());
        $baseTransaction->addConflict($message);
        $baseTransaction->sendResult(false, $transaction);
    }

    public function onTransactionSuccess(Transaction $transaction): void
    {
        $baseTransaction = TransactionAdapter::getTransaction($transaction->originalTransaction()->first());
        $baseTransaction->sendResult(true, $transaction);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('transaction.saved', 'App\Listeners\TransactionListener@onTransactionSaved');
        $events->listen('transaction.successful', 'App\Listeners\TransactionListener@onTransactionSuccess');
        $events->listen('transaction.failed', 'App\Listeners\TransactionListener@onTransactionFailed');
    }
}
