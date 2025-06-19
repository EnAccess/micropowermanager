<?php

namespace Inensus\SwiftaPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction) {
        $transactionProvider = resolve('SwiftaPaymentProvider');
        $transactionProvider->sendResult(true, $transaction);
    }

    public function handle(Transaction $transaction) {
        $this->onTransactionSuccess($transaction);
    }
}
