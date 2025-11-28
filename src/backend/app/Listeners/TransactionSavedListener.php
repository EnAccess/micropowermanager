<?php

namespace App\Listeners;

use App\Events\TransactionSavedEvent;
use App\Services\Interfaces\ITransactionProvider;

class TransactionSavedListener {
    public function onTransactionSaved(ITransactionProvider $transactionProvider): void {
        // echos the confirmation output
        $transactionProvider->confirm();
    }

    public function handle(TransactionSavedEvent $event): void {
        $this->onTransactionSaved($event->transactionProvider);
    }
}
