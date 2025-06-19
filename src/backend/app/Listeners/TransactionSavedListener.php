<?php

namespace App\Listeners;

use MPM\Transaction\Provider\ITransactionProvider;

class TransactionSavedListener {
    public function onTransactionSaved(ITransactionProvider $transactionProvider): void {
        // echos the confirmation output
        $transactionProvider->confirm();
    }

    public function handle(ITransactionProvider $transactionProvider): void {
        $this->onTransactionSaved($transactionProvider);
    }
}
