<?php

namespace Inensus\MesombPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Log;
use Inensus\MesombPaymentProvider\Providers\MesombTransactionProvider;

class TransactionFailedListener {
    public function onTransactionFailed(Transaction $transaction, $message = null): void {
        $transactionProvider = resolve(MesombTransactionProvider::class);
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
