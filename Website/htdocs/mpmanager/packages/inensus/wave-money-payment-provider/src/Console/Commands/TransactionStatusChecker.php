<?php

namespace Inensus\WaveMoneyPaymentProvider\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Exceptions\TransactionNotInitializedException;
use App\Jobs\ProcessPayment;
use App\Jobs\TokenProcessor;
use App\Misc\TransactionDataContainer;
use App\Models\MpmPlugin;
use App\Traits\ScheduledPluginCommand;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;


class TransactionStatusChecker extends AbstractSharedCommand
{
    use ScheduledPluginCommand;

    protected $signature = 'wave-money-payment-provider:transactionStatusCheck';
    protected $description = 'Retries to process transactions which are not processed by MicroPowerManager yet.';


    public function __construct(
        private WaveMoneyTransactionService $waveMoneyTransactionService,
    ) {
        parent::__construct();

    }

    public function handle(): void
    {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER)) {
            return;
        }

        $waveMoneyTransactions =
            $this->waveMoneyTransactionService->getByStatus(WaveMoneyTransaction::STATUS_COMPLETED_BY_WAVE_MONEY);

        $waveMoneyTransactions->filter(function ($waveMoneyTransaction) {
            return $waveMoneyTransaction->attempts <= WaveMoneyTransaction::MAX_ATTEMPTS;
        })->each(function ($waveMoneyTransaction) {
            $this->waveMoneyTransactionService->update($waveMoneyTransaction,
                ['attempts' => $waveMoneyTransaction->attempts + 1]);

            if (config('app.env') === 'production') {//production queue
                $queue = 'payment';
            } elseif (config('app.env') === 'staging') {
                $queue = 'staging_payment';
            } else { // local queue‚
                $queue = 'local_payment';
            }

            $transaction = $waveMoneyTransaction->transaction()->first();
            TokenProcessor::dispatch($this->initializeTransactionDataContainer($transaction))
                ->allOnConnection('redis')
                ->onQueue(\config('services.queues.token'));
        });
    }

    /**
     * @return array
     */
    private function initializeTransactionDataContainer($transaction): TransactionDataContainer|array
    {
        try {
            //create an object for the token job
            $transactionData = TransactionDataContainer::initialize($transaction);
        } catch (\Exception $e) {
            event('transaction.failed', [$transaction, $e->getMessage()]);
            throw new TransactionNotInitializedException($e->getMessage());
        }
        return $transactionData;
    }
}
