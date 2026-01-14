<?php

namespace Inensus\SwiftaPaymentProvider\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;

class TransactionStatusChecker extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 7;

    protected $signature = 'swifta-payment-provider:transactionStatusCheck';
    protected $description = 'Update the Swifta Transaction status if still REQUESTED at 00:00';

    public function __construct(private SwiftaTransactionService $swiftaTransactionService) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Swifta Transaction Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('transactionStatusCheck command started at '.$startedAt);
        $this->swiftaTransactionService->setRequestedTransactionsStatusFailed();
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
