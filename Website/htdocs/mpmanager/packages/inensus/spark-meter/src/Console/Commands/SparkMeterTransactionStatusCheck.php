<?php

namespace Inensus\SparkMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\SparkMeter\Services\TransactionService;
use Inensus\SparkMeter\Models\SmTransaction;

class SparkMeterTransactionStatusCheck extends Command
{
    protected $signature = 'spark-meter:transactionStatusCheck';
    protected $description = 'Checks status of Spark Meter transactions';

    private $transactionService;
    private $smTransaction;

    public function __construct(
        TransactionService $transactionService,
        SmTransaction $smTransaction
    ) {
        parent::__construct();
        $this->transactionService = $transactionService;
        $this->smTransaction = $smTransaction;
    }

    public function handle(): void
    {
        $smTransactions = $this->smTransaction->newQuery()
            ->where('status', 'error')
            ->orWhere('status', 'not-processed')
            ->orWhere('status', 'pending')
            ->whereNotNull('external_id')
            ->get();
        foreach ($smTransactions as $key => $smTransaction) {
            $this->transactionService->updateTransactionStatus($smTransaction);
        }
    }
}
