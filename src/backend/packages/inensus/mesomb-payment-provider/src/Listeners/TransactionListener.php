<?php

namespace Inensus\MesombPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TransactionListener {
    public function onTransactionFailed(Transaction $transaction, $message = null): void {
        $transactionProvider = resolve('MesombPaymentProvider');
        $transactionProvider->addConflict($message);
        if (config('app.debug')) {
            Log::debug('Transaction failed');
        }
        $transactionProvider->sendResult(false, $transaction);
    }

    public function onTransactionSuccess(Transaction $transaction) {
        $transactionProvider = resolve('MesombPaymentProvider');
        $transactionProvider->sendResult(true, $transaction);
    }

    public function subscribe(Dispatcher $events) {
        $events->listen(
            'transaction.successful',
            'Inensus\MesombPaymentProvider\Listeners\TransactionListener@onTransactionSuccess'
        );
        $events->listen(
            'transaction.failed',
            'Inensus\MesombPaymentProvider\Listeners\TransactionListener@onTransactionFailed'
        );
    }
}
