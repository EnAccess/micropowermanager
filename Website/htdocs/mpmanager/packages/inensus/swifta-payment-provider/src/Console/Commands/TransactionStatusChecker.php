<?php


namespace Inensus\SwiftaPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;

class TransactionStatusChecker extends Command
{
    protected $signature = 'swifta-payment-provider:transactionStatusCheck';
    protected $description = 'Update the Swifta Transaction status if still -2 at 00:00';

    private $swiftaTransactionService;

    public function __construct(SwiftaTransactionService $swiftaTransactionService)
    {
        parent::__construct();
        $this->swiftaTransactionService = $swiftaTransactionService;
    }

    public function handle()
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Swifta Transaction Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('transactionStatusCheck command started at ' . $startedAt);
        $this->swiftaTransactionService->setUnProcessedTransactionsStatusAsRejected();
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}