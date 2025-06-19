<?php

namespace Inensus\SwiftaPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction) {
        $transactionProvider = resolve('SwiftaPaymentProvider');
        $transactionProvider->sendResult(true, $transaction);
    }

    public function handle(Transaction $transaction) {
        $this->onTransactionSuccess($transaction);
    }
}
