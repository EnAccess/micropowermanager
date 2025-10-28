<?php

namespace Inensus\SparkMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Inensus\SparkMeter\Exceptions\CronJobException;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmSmsNotifiedCustomer;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\SmSmsNotifiedCustomerService;
use Inensus\SparkMeter\Services\SmSmsSettingService;
use Inensus\SparkMeter\Services\TransactionService;
use Inensus\SparkMeter\Sms\Senders\SparkSmsConfig;
use Inensus\SparkMeter\Sms\SparkSmsTypes;

class SparkMeterSmsNotifier extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'spark-meter:smsNotifier';
    protected $description = 'Notifies customers on payments and low balance limits for SparkMeters';

    public function __construct(
        private SmSmsSettingService $smsSettingsService,
        private TransactionService $smTransactionService,
        private SmSmsNotifiedCustomerService $smSmsNotifiedCustomerService,
        private CustomerService $smCustomerService,
        private SmsService $smsService,
    ) {
        parent::__construct();
    }

    /**
     * @param Collection<int, SmSmsNotifiedCustomer> $smsNotifiedCustomers
     * @param Collection<int, SmCustomer>            $customers
     */
    private function sendTransactionNotifySms(
        int $transactionMin,
        Collection $smsNotifiedCustomers,
        Collection $customers,
    ): void {
        $this->smTransactionService->getSparkTransactions($transactionMin)
            ->each(function ($smTransaction) use (
                $smsNotifiedCustomers,
                $customers
            ): true {
                $smsNotifiedCustomers = $smsNotifiedCustomers->where(
                    'notify_id',
                    $smTransaction->id
                )->where('customer_id', $smTransaction->customer_id)->first();
                if ($smsNotifiedCustomers) {
                    return true;
                }
                $notifyCustomer = $customers->filter(fn ($customer): bool => $customer->customer_id == $smTransaction->customer_id)->first();

                if (!$notifyCustomer) {
                    return true;
                }

                if (
                    $notifyCustomer->mpmPerson->addresses->isEmpty()
                    || $notifyCustomer->mpmPerson->addresses[0]->phone === null
                    || $notifyCustomer->mpmPerson->addresses[0]->phone === ''
                ) {
                    return true;
                }
                $this->smsService->sendSms(
                    $smTransaction->thirdPartyTransaction->transaction->toArray(),
                    SmsTypes::TRANSACTION_CONFIRMATION,
                    SmsConfigs::class
                );

                $this->smSmsNotifiedCustomerService->createTransactionSmsNotify(
                    $notifyCustomer->customer_id,
                    $smTransaction->id
                );

                return true;
            });
    }

    /**
     * @param Collection<int, SmCustomer>            $customers
     * @param Collection<int, SmSmsNotifiedCustomer> $smsNotifiedCustomers
     */
    private function sendLowBalanceWarningNotifySms($customers, Collection $smsNotifiedCustomers): void {
        $customers->each(function ($customer) use (
            $smsNotifiedCustomers
        ): true {
            $notifiedCustomer = $smsNotifiedCustomers->where('notify_type', 'low_balance')->where(
                'customer_id',
                $customer->customer_id
            )->first();
            if ($notifiedCustomer) {
                return true;
            }
            if ($customer->credit_balance > $customer->low_balance_limit) {
                return true;
            }
            if (
                $customer->mpmPerson->addresses->isEmpty()
                || $customer->mpmPerson->addresses[0]->phone === null
                || $customer->mpmPerson->addresses[0]->phone === ''
            ) {
                return true;
            }
            $this->smsService->sendSms(
                $customer->toArray(),
                SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER,
                SparkSmsConfig::class
            );
            $this->smSmsNotifiedCustomerService->createLowBalanceSmsNotify($customer->customer_id);

            return true;
        });
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Spark Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('smsNotifier command started at '.$startedAt);
        try {
            $smsSettings = $this->smsSettingsService->getSmsSettings();
            $transactionsSettings = $smsSettings->where('state', 'Transactions')->first();

            if (!$transactionsSettings) {
                throw new CronJobException('Transaction min is not set');
            }
            $transactionMin = $transactionsSettings->not_send_elder_than_mins;

            $lowBalanceWarningSetting = $smsSettings->where('state', 'Low Balance Warning')->first();

            if (!$lowBalanceWarningSetting) {
                throw new CronJobException('Low balance min is not set');
            }

            $lowBalanceMin = $lowBalanceWarningSetting->not_send_elder_than_mins;
            $smsNotifiedCustomers = $this->smSmsNotifiedCustomerService->getSmsNotifiedCustomers();
            $customers = $this->smCustomerService->getSparkCustomersWithAddress();

            if ($customers->count() && $smsNotifiedCustomers->count()) {
                $this->sendTransactionNotifySms($transactionMin, $smsNotifiedCustomers, $customers);
                $this->sendLowBalanceWarningNotifySms($customers
                    ->where(
                        'updated_at',
                        '>=',
                        Carbon::now()->subMinutes($lowBalanceMin)
                    ), $smsNotifiedCustomers);
            }
        } catch (CronJobException|\Exception $e) {
            $this->warn('dataSync command is failed. message => '.$e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
