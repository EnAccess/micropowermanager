<?php

namespace App\Plugins\MesombPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use App\Plugins\MesombPaymentProvider\Providers\MesombTransactionProvider;
use Illuminate\Support\Facades\Log;

class TransactionFailedListener {
    public function onTransactionFailed(Transaction $transaction, ?string $message = null): void {
        $transactionProvider = resolve(MesombTransactionProvider::class);
        $transactionProvider->addConflict($message);
        if (config('app.debug')) {
            Log::debug('Transaction failed');
        }
        $transactionProvider->sendResult(false, $transaction);
    }

    public function handle(Transaction $transaction, ?string $message = null): void {
        $this->onTransactionFailed($transaction, $message);
    }
}
