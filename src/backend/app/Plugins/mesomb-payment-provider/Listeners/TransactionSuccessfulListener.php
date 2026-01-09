<?php

namespace Inensus\MesombPaymentProvider\Listeners;

use App\Models\Transaction\Transaction;
use Inensus\MesombPaymentProvider\Providers\MesombTransactionProvider;

class TransactionSuccessfulListener {
    public function onTransactionSuccess(Transaction $transaction): void {
        $transactionProvider = resolve(MesombTransactionProvider::class);
        $transactionProvider->sendResult(true, $transaction);
    }

    public function handle(Transaction $transaction): void {
        $this->onTransactionSuccess($transaction);
    }
}
