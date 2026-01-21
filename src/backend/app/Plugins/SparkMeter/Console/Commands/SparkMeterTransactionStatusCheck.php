<?php

namespace App\Plugins\SparkMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\MpmPlugin;
use App\Plugins\SparkMeter\Models\SmTransaction;
use App\Plugins\SparkMeter\Services\TransactionService;
use App\Traits\ScheduledPluginCommand;

class SparkMeterTransactionStatusCheck extends AbstractSharedCommand {
    use ScheduledPluginCommand;

    protected $signature = 'spark-meter:transactionStatusCheck';
    protected $description = 'Checks status of Spark Meter transactions';

    public function __construct(
        private TransactionService $transactionService,
        private SmTransaction $smTransaction,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::SPARK_METER)) {
            return;
        }

        $smTransactions = $this->smTransaction->newQuery()
            ->where('status', 'error')
            ->orWhere('status', 'not-processed')
            ->orWhere('status', 'pending')
            ->whereNotNull('external_id')
            ->get();
        foreach ($smTransactions as $smTransaction) {
            $this->transactionService->updateTransactionStatus($smTransaction);
        }
    }
}
