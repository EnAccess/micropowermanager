<?php

namespace Inensus\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\Sms;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\SteamaMeter\Exceptions\CronJobException;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Services\SteamaSmsNotifiedCustomerService;
use Inensus\SteamaMeter\Services\SteamaSmsSettingService;
use Inensus\SteamaMeter\Services\SteamaTransactionsService;
use Inensus\SteamaMeter\Sms\Senders\SteamaSmsConfig;
use Inensus\SteamaMeter\Sms\SteamaSmsTypes;

class SteamaSmsNotifier extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'steama-meter:smsNotifier';
    protected $description = 'Notifies customers on payments and low balance limits for SteamaCoMeters';

    private $smsSettingsService;
    private $sms;
    private $steamaTransactionService;
    private $steamaSmsNotifiedCustomerService;
    private $steamaCustomerService;
    private $smsService;

    public function __construct(
        SteamaSmsSettingService $smsSettingService,
        Sms $sms,
        SteamaTransactionsService $steamaTransactionsService,
        SteamaSmsNotifiedCustomerService $steamaSmsNotifiedCustomerService,
        SteamaCustomerService $steamaCustomerService,
        SmsService $smsService,
    ) {
        parent::__construct();
        $this->smsSettingsService = $smsSettingService;
        $this->sms = $sms;
        $this->steamaTransactionService = $steamaTransactionsService;
        $this->steamaSmsNotifiedCustomerService = $steamaSmsNotifiedCustomerService;
        $this->steamaCustomerService = $steamaCustomerService;
        $this->smsService = $smsService;
    }

    private function sendTransactionNotifySms($transactionMin, $smsNotifiedCustomers, $customers) {
        $this->steamaTransactionService->getSteamaTransactions($transactionMin)
            ->each(function ($steamaTransaction) use (
                $smsNotifiedCustomers,
                $customers
            ) {
                $smsNotifiedCustomers = $smsNotifiedCustomers->where(
                    'notify_id',
                    $steamaTransaction->id
                )->where('customer_id', $steamaTransaction->customer_id)->first();
                if ($smsNotifiedCustomers) {
                    return true;
                }
                $notifyCustomer = $customers->filter(function ($customer) use ($steamaTransaction) {
                    return $customer->customer_id == $steamaTransaction->customer_id;
                })->first();

                if (!$notifyCustomer) {
                    return true;
                }

                if (
                    !$notifyCustomer->mpmPerson->addresses
                    || $notifyCustomer->mpmPerson->addresses[0]->phone === null
                    || $notifyCustomer->mpmPerson->addresses[0]->phone === ''
                ) {
                    return true;
                }
                $this->smsService->sendSms(
                    $steamaTransaction->thirdPartyTransaction->transaction,
                    SmsTypes::TRANSACTION_CONFIRMATION,
                    SmsConfigs::class
                );

                $this->steamaSmsNotifiedCustomerService->createTransactionSmsNotify(
                    $notifyCustomer->customer_id,
                    $steamaTransaction->id
                );

                return true;
            });
    }

    private function sendLowBalanceWarningNotifySms($customers, $smsNotifiedCustomers, $lowBalanceMin) {
        $customers->each(function ($customer) use (
            $smsNotifiedCustomers
        ) {
            $notifiedCustomer = $smsNotifiedCustomers->where('notify_type', 'low_balance')->where(
                'customer_id',
                $customer->customer_id
            )->first();
            if ($notifiedCustomer) {
                return true;
            }
            if ($customer->account_balance > $customer->low_balance_warning) {
                return true;
            }
            if (
                !$customer->mpmPerson->addresses || $customer->mpmPerson->addresses[0]->phone === null
                || $customer->mpmPerson->addresses[0]->phone === ''
            ) {
                return true;
            }
            $this->smsService->sendSms(
                $customer,
                SteamaSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER,
                SteamaSmsConfig::class
            );

            $this->steamaSmsNotifiedCustomerService->createLowBalanceSmsNotify($customer->customer_id);

            return true;
        });
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# SteamaCo Meter Package #');
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
            $smsNotifiedCustomers = $this->steamaSmsNotifiedCustomerService->getSteamaSmsNotifiedCustomers();
            $customers = $this->steamaCustomerService->getSteamaCustomersWithAddress();

            if ($customers->count() && $smsNotifiedCustomers->count()) {
                $this->sendTransactionNotifySms($transactionMin, $smsNotifiedCustomers, $customers);
                $this->sendLowBalanceWarningNotifySms($customers
                    ->where(
                        'updated_at',
                        '>=',
                        Carbon::now()->subMinutes($lowBalanceMin)
                    ), $smsNotifiedCustomers, $lowBalanceMin);
            }
        } catch (CronJobException $e) {
            $this->warn('dataSync command is failed. message => '.$e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
