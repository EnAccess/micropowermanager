<?php

namespace App\Plugins\SparkMeter\Jobs;

use App\Jobs\AbstractJob;
use App\Plugins\SparkMeter\Exceptions\CronJobException;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Models\SmSmsNotifiedCustomer;
use App\Plugins\SparkMeter\Services\CustomerService;
use App\Plugins\SparkMeter\Services\SmSmsNotifiedCustomerService;
use App\Plugins\SparkMeter\Services\SmSmsSettingService;
use App\Plugins\SparkMeter\Services\TransactionService;
use App\Plugins\SparkMeter\Sms\Senders\SparkSmsConfig;
use App\Plugins\SparkMeter\Sms\SparkSmsTypes;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SendSparkMeterSmsNotifications extends AbstractJob {
    public function __construct(?int $companyId = null) {
        parent::__construct($companyId);

        $this->onConnection('redis');
        $this->onQueue('spark_meter');
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array {
        return [new WithoutOverlapping('sms-notifier-'.$this->companyId)->dontRelease()];
    }

    public function executeJob(): void {
        $smsSettingsService = resolve(SmSmsSettingService::class);
        $smSmsNotifiedCustomerService = resolve(SmSmsNotifiedCustomerService::class);
        $smCustomerService = resolve(CustomerService::class);
        $smTransactionService = resolve(TransactionService::class);
        $smsService = resolve(SmsService::class);

        $smsSettings = $smsSettingsService->getSmsSettings();
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
        $smsNotifiedCustomers = $smSmsNotifiedCustomerService->getSmsNotifiedCustomers();
        $customers = $smCustomerService->getSparkCustomersWithAddress();

        if ($customers->count() && $smsNotifiedCustomers->count()) {
            $this->sendTransactionNotifySms(
                $transactionMin,
                $smsNotifiedCustomers,
                $customers,
                $smTransactionService,
                $smSmsNotifiedCustomerService,
                $smsService
            );
            $this->sendLowBalanceWarningNotifySms(
                $customers->where(
                    'updated_at',
                    '>=',
                    Carbon::now()->subMinutes($lowBalanceMin)
                ),
                $smsNotifiedCustomers,
                $smSmsNotifiedCustomerService,
                $smsService
            );
        }
    }

    /**
     * @param Collection<int, SmSmsNotifiedCustomer> $smsNotifiedCustomers
     * @param Collection<int, SmCustomer>            $customers
     */
    private function sendTransactionNotifySms(
        int $transactionMin,
        Collection $smsNotifiedCustomers,
        Collection $customers,
        TransactionService $smTransactionService,
        SmSmsNotifiedCustomerService $smSmsNotifiedCustomerService,
        SmsService $smsService,
    ): void {
        $smTransactionService->getSparkTransactions($transactionMin)
            ->each(function ($smTransaction) use (
                $smsNotifiedCustomers,
                $customers,
                $smSmsNotifiedCustomerService,
                $smsService
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
                $smsService->sendSms(
                    $smTransaction->thirdPartyTransaction->transaction->toArray(),
                    SmsTypes::TRANSACTION_CONFIRMATION,
                    SmsConfigs::class
                );

                $smSmsNotifiedCustomerService->createTransactionSmsNotify(
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
    private function sendLowBalanceWarningNotifySms(
        $customers,
        Collection $smsNotifiedCustomers,
        SmSmsNotifiedCustomerService $smSmsNotifiedCustomerService,
        SmsService $smsService,
    ): void {
        $customers->each(function ($customer) use (
            $smsNotifiedCustomers,
            $smSmsNotifiedCustomerService,
            $smsService
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
            $smsService->sendSms(
                $customer,
                SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER,
                SparkSmsConfig::class
            );
            $smSmsNotifiedCustomerService->createLowBalanceSmsNotify($customer->customer_id);

            return true;
        });
    }
}
