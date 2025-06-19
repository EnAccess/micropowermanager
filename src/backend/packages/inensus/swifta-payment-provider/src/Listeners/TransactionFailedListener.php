<?php

namespace Inensus\SwiftaPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionFailedListener {
    public function onTransactionFailed(Transaction $transaction, $message = null): void {
        $transactionProvider = resolve('SwiftaPaymentProvider');
        $transactionProvider->conflict($message, $transaction);
        if (config('app.debug')) {
            Log::debug('Transaction failed');
        }
        $transactionProvider->sendResult(false, $transaction);
    }

    public function handle(Transaction $transaction, $message = null) {
        $this->onTransactionFailed($transaction, $message);
    }
}
