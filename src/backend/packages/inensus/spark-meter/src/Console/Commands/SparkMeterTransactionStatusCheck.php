<?php

namespace Inensus\SparkMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\MpmPlugin;
use App\Traits\ScheduledPluginCommand;
use Inensus\SparkMeter\Models\SmTransaction;
use Inensus\SparkMeter\Services\TransactionService;

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
        foreach ($smTransactions as $key => $smTransaction) {
            $this->transactionService->updateTransactionStatus($smTransaction);
        }
    }
}
