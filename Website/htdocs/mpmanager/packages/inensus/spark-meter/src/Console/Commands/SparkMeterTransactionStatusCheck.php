<?php

namespace Inensus\SparkMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Illuminate\Console\Command;
use Inensus\SparkMeter\Services\TransactionService;
use Inensus\SparkMeter\Models\SmTransaction;

class SparkMeterTransactionStatusCheck extends AbstractSharedCommand
{
    const MPM_PLUGIN_ID = 2;
    use ScheduledPluginCommand;

    protected $signature = 'spark-meter:transactionStatusCheck';
    protected $description = 'Checks status of Spark Meter transactions';


    public function __construct(
        private  TransactionService $transactionService,
        private  SmTransaction $smTransaction
    ) {
        parent::__construct();

    }


   public function handle(): void
    {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

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
