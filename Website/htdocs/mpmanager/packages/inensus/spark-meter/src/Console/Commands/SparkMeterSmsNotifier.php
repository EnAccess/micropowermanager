<?php

namespace Inensus\SparkMeter\Console\Commands;

use App\Models\Sms;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\SparkMeter\Exceptions\CronJobException;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\SmSmsNotifiedCustomerService;
use Inensus\SparkMeter\Services\SmSmsSettingService;
use Inensus\SparkMeter\Services\TransactionService;
use Inensus\SparkMeter\Sms\Senders\SparkSmsConfig;
use Inensus\SparkMeter\Sms\SparkSmsTypes;


class SparkMeterSmsNotifier extends Command
{
    protected $signature = 'spark-meter:smsNotifier';
    protected $description = 'Notifies customers on payments and low balance limits';

    private $smsSettingsService;
    private $sms;
    private $smTransactionService;
    private $smSmsNotifiedCustomerService;
    private $smCustomerService;
    private $smsService;

    public function __construct(
        SmSmsSettingService $smsSettingService,
        Sms $sms,
        TransactionService $smTransactionsService,
        SmSmsNotifiedCustomerService $smSmsNotifiedCustomerService,
        CustomerService $smCustomerService,
        SmsService $smsService
    ) {
        parent::__construct();
        $this->smsSettingsService = $smsSettingService;
        $this->sms = $sms;
        $this->smTransactionService = $smTransactionsService;
        $this->smSmsNotifiedCustomerService = $smSmsNotifiedCustomerService;
        $this->smCustomerService = $smCustomerService;
        $this->smsService = $smsService;
    }

    public function handle()
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Spark Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('smsNotifier command started at ' . $startedAt);
        try {
            $smsSettings = $this->smsSettingsService->getSmsSettings();
            $transactionMin = $smsSettings->where('state', 'Transactions')->first()->not_send_elder_than_mins;
            $lowBalanceMin = $smsSettings->where('state', 'Low Balance Warning')->first()->not_send_elder_than_mins;
            $smsNotifiedCustomers = $this->smSmsNotifiedCustomerService->getSmsNotifiedCustomers();
            $customers = $this->smCustomerService->getSparkCustomersWithAddress();
            $this->sendTransactionNotifySms($transactionMin, $smsNotifiedCustomers, $customers);
            $this->sendLowBalanceWarningNotifySms($customers
                ->where(
                    'updated_at',
                    '>=',
                    Carbon::now()->subMinutes($lowBalanceMin)
                ), $smsNotifiedCustomers, $lowBalanceMin);
        } catch (CronJobException $e) {
            $this->warn('dataSync command is failed. message => ' . $e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }

    private function sendTransactionNotifySms($transactionMin, $smsNotifiedCustomers, $customers)
    {
        $this->smTransactionService->getSparkTransactions($transactionMin)
            ->each(function ($smTransaction) use (
                $transactionMin,
                $smsNotifiedCustomers,
                $customers
            ) {
                $smsNotifiedCustomers = $smsNotifiedCustomers->where(
                    'notify_id',
                    $smTransaction->id
                )->where('customer_id', $smTransaction->customer_id)->first();
                if ($smsNotifiedCustomers) {
                    return true;
                }
                $notifyCustomer = $customers->filter(function ($customer) use ($smTransaction) {
                    return $customer->customer_id == $smTransaction->customer_id;
                })->first();

                if (!$notifyCustomer) {
                    return true;
                }
                if (
                    !$notifyCustomer->mpmPerson->addresses ||
                    $notifyCustomer->mpmPerson->addresses[0]->phone === null ||
                    $notifyCustomer->mpmPerson->addresses[0]->phone === ""
                ) {
                    return true;
                }
                $this->smsService->sendSms($smTransaction->thirdPartyTransaction->transaction,
                    SmsTypes::TRANSACTION_CONFIRMATION,
                    SmsConfigs::class);
                $this->smSmsNotifiedCustomerService->createTransactionSmsNotify(
                    $notifyCustomer->customer_id,
                    $smTransaction->id
                );
                return true;
            });
    }

    private function sendLowBalanceWarningNotifySms($customers, $smsNotifiedCustomers, $lowBalanceMin)
    {
        $customers->each(function ($customer) use (
            $smsNotifiedCustomers,
            $lowBalanceMin
        ) {
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
                !$customer->mpmPerson->addresses || $customer->mpmPerson->addresses[0]->phone === null ||
                $customer->mpmPerson->addresses[0]->phone === ""
            ) {
                return true;
            }
            $this->smsService->sendSms($customer,
                SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER,
                SparkSmsConfig::class);
            $this->smSmsNotifiedCustomerService->createLowBalanceSmsNotify($customer->customer_id);
            return true;
        });
    }
}
