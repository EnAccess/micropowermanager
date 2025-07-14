<?php

namespace Inensus\MesombPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction) {
        $transactionProvider = resolve('MesombPaymentProvider');
        $transactionProvider->sendResult(true, $transaction);
    }

    public function handle(Transaction $transaction): void {
        $this->onTransactionSuccess($transaction);
    }
}
