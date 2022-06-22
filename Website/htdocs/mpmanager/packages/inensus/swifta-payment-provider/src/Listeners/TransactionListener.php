<?php

namespace Inensus\SwiftaPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TransactionListener
{

    public function onTransactionFailed(Transaction $transaction, $message = null): void
    {
        $transactionProvider = resolve('SwiftaPaymentProvider');
        $transactionProvider->conflict($message,$transaction);
        if (config('app.debug')) {
            Log::debug('Transaction failed');
        }
        $transactionProvider->sendResult(false, $transaction);
    }

    public function onTransactionSuccess(Transaction $transaction)
    {
        $transactionProvider = resolve('SwiftaPaymentProvider');
        $transactionProvider->sendResult(true, $transaction);

    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('transaction.successful',
            'Inensus\SwiftaPaymentProvider\Listeners\TransactionListener@onTransactionSuccess'
        );
        $events->listen('transaction.failed',
            'Inensus\SwiftaPaymentProvider\Listeners\TransactionListener@onTransactionFailed'
        );
    }
}