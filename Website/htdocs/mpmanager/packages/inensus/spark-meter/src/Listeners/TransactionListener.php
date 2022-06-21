<?php

namespace Inensus\SparkMeter\Listeners;

use App\Models\Transaction\Transaction;
use Illuminate\Contracts\Events\Dispatcher;
use Inensus\SparkMeter\Services\TransactionService;
use Inensus\SparkMeter\Models\SmTransaction;

class TransactionListener
{
    private $transactionService;
    private $smTransaction;
    public function __construct(
        TransactionService $transactionService,
        SmTransaction $smTransaction
    ) {
        $this->transactionService = $transactionService;
        $this->smTransaction = $smTransaction;
    }

    /**
     * Sets the in_use to true
     * @param Transaction $transaction
     */
    public function onTransactionSuccess(Transaction $transaction)
    {
        $smTransaction = $this->smTransaction->newQuery()->where('mpm_transaction_id', $transaction->id)->first();
        if ($smTransaction) {
            $this->transactionService->updateTransactionStatus($smTransaction);
        }
    }
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'transaction.successful',
            'Inensus\SparkMeter\Listeners\TransactionListener@onTransactionSuccess'
        );
    }
}
