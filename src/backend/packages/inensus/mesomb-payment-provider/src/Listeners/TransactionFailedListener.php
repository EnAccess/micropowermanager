<?php

namespace Inensus\MesombPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionFailedListener {
    public function onTransactionFailed(Transaction $transaction, $message = null): void {
        $transactionProvider = resolve('MesombPaymentProvider');
        $transactionProvider->addConflict($message);
        if (config('app.debug')) {
            Log::debug('Transaction failed');
        }
        $transactionProvider->sendResult(false, $transaction);
    }

    public function handle(Transaction $transaction, $message = null): void {
        $this->onTransactionFailed($transaction, $message);
    }
}
