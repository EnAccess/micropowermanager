<?php

namespace App\Plugins\SparkMeter\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\Transaction\Transaction;
use App\Plugins\SparkMeter\Models\SmTransaction;
use App\Plugins\SparkMeter\Services\TransactionService;

class TransactionListener {
    public function __construct(private TransactionService $transactionService, private SmTransaction $smTransaction) {}

    /**
     * Sets the in_use to true.
     */
    public function onTransactionSuccess(Transaction $transaction): void {
        $smTransaction = $this->smTransaction->newQuery()->where('external_id', $transaction->id)->first();
        if ($smTransaction) {
            $this->transactionService->updateTransactionStatus($smTransaction);
        }
    }

    public function handle(TransactionSuccessfulEvent $event): void {
        $this->onTransactionSuccess($event->transaction);
    }
}
