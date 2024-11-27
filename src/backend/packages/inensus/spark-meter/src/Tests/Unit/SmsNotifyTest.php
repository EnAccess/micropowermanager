<?php

namespace Inensus\SparkMeter\Tests\Unit;

use App\Models\Address\Address;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\SmsBody;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmSetting;
use Inensus\SparkMeter\Models\SmSmsBody;
use Inensus\SparkMeter\Models\SmSmsNotifiedCustomer;
use Inensus\SparkMeter\Models\SmSmsSetting;
use Inensus\SparkMeter\Models\SmSyncAction;
use Inensus\SparkMeter\Models\SmSyncSetting;
use Inensus\SparkMeter\Models\SmTransaction;
use Inensus\SparkMeter\Sms\Senders\SparkSmsConfig;
use Inensus\SparkMeter\Sms\SparkSmsTypes;
use Tests\TestCase;

class SmsNotifyTest extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function isLowBalanceNotifySend() {
        Queue::fake();
        $this->initializeData();
        $lowBalanceMin = SmSmsSetting::query()->where(
            'state',
            'Low Balance Warning'
        )->first()->not_send_elder_than_mins;

        $customers = SmCustomer::query()->with([
            'mpmPerson.addresses',
        ])->whereHas('mpmPerson.addresses', function ($q) {
            return $q->where('is_primary', 1);
        })->where(
            'updated_at',
            '>=',
            Carbon::now()->subMinutes($lowBalanceMin)
        )->get();

        $smsNotifiedCustomers = SmSmsNotifiedCustomer::query()->get();
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

            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($customer, SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER, SparkSmsConfig::class);

            SmSmsNotifiedCustomer::query()->create([
                'customer_id' => $customer->customer_id,
                'notify_type' => 'low_balance',
            ]);

            return true;
        });
    }

    /** @test */
    public function isTransactionNotifySend() {
        Queue::fake();
        $data = $this->initializeData();
        $this->initializeSparkTransaction($data['customer']);
        $transactionMin = SmSmsSetting::query()->where(
            'state',
            'Transactions'
        )->first()->not_send_elder_than_mins;
        $smsNotifiedCustomers = SmSmsNotifiedCustomer::query()->get();
        $customers = SmCustomer::query()->with([
            'mpmPerson.addresses',
        ])->whereHas('mpmPerson.addresses', function ($q) {
            return $q->where('is_primary', 1);
        })->get();
        SmTransaction::query()->with(['thirdPartyTransaction.transaction'])->where(
            'timestamp',
            '>=',
            Carbon::now()->subMinutes($transactionMin)
        )->where('status', 'processed')->get()->each(function ($sparkTransaction) use (
            $smsNotifiedCustomers,
            $customers
        ) {
            $smsNotifiedCustomers = $smsNotifiedCustomers->where(
                'notify_id',
                $sparkTransaction->id
            )->where('customer_id', $sparkTransaction->customer_id)->first();
            if ($smsNotifiedCustomers) {
                return true;
            }
            $notifyCustomer = $customers->filter(function ($customer) use ($sparkTransaction) {
                return $customer->customer_id == $sparkTransaction->customer_id;
            })->first();
            if (!$notifyCustomer) {
                return true;
            }
            if (
                !$notifyCustomer->mpmPerson->addresses || $notifyCustomer->mpmPerson->addresses[0]->phone === null
                || $notifyCustomer->mpmPerson->addresses[0]->phone === ''
            ) {
                return true;
            }

            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($sparkTransaction->thirdPartyTransaction->transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);

            SmSmsNotifiedCustomer::query()->create([
                'customer_id' => $notifyCustomer->customer_id,
                'notify_type' => 'transaction',
                'notify_id' => $sparkTransaction->id,
            ]);

            return true;
        });
    }

    /** @test */
    public function isMaxAttemptNotifySend() {
        Queue::fake();
        $this->addSyncSettings();
        $this->initializeAdminData();
        $syncActions = SmSyncAction::query()->where('next_sync', '<=', Carbon::now())
            ->orderBy('next_sync')->get();
        $oldNextSync = $syncActions->first()->next_sync;
        $newNextSync = null;
        SmSyncSetting::query()->get()->each(function ($syncSetting) use ($syncActions) {
            $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();

            if (!$syncAction) {
                return true;
            }
            if ($syncAction->attempts >= $syncSetting->max_attempts) {
                $nextSync = Carbon::parse($syncAction->next_sync)->addHours(2);
                $syncAction->next_sync = $nextSync;
                $adminAddress = Address::query()->whereHasMorph(
                    'owner',
                    [User::class]
                )->first();
                if (!$adminAddress) {
                    return true;
                }
                $data = [
                    'message' => $syncSetting->action_name.
                        ' synchronization has failed by unrealizable reason that occurred on source API.
                         It is going to be retried at '.$nextSync,
                    'phone' => $adminAddress->phone,
                ];
                $smsService = app()->make(SmsService::class);
                $smsService->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);
            }

            return true;
        });
        $this->assertLessThan($oldNextSync, $newNextSync);
    }

    private function initializeData() {
        $this->addSmsSettings();
        $this->addSmsBodies();
        // create person
        factory(MainSettings::class)->create();

        // create person
        factory(Person::class)->create();
        // create meter-tariff
        factory(MeterTariff::class)->create();

        // create meter-type
        MeterType::query()->create([
            'online' => 0,
            'phase' => 1,
            'max_current' => 10,
        ]);

        // create calin manufacturer
        Manufacturer::query()->create([
            'name' => 'Spark Meters',
            'website' => 'https://www.sparkmeter.io/',
            'api_name' => 'SparkMeterApi',
        ]);

        // create meter
        Meter::query()->create([
            'serial_number' => '4700005646',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
        ]);

        // associate meter with a person
        $p = Person::query()->first();
        $p->meters()->create([
            'tariff_id' => 1,
            'meter_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
        ]);

        // associate address with a person
        $address = Address::query()->make([
            'phone' => '+905494322161',
            'is_primary' => 1,
            'owner_type' => 'person',
        ]);
        $address->owner()->associate($p);
        $address->save();
        SmCustomer::query()->create([
            'site_id' => 1,
            'customer_id' => 1,
            'mpm_customer_id' => $p->id,
            'credit_balance' => 100,
            'low_balance_limit' => 150,
            'hash' => 'xxxxxxxxx',
        ]);

        return ['customer' => $p];
    }

    private function initializeSparkTransaction($customer) {
        $sparkTransaction = SmTransaction::query()->create([
            'site_id' => 1,
            'customer_id' => $customer->id,
            'transaction_id' => '1111',
            'status' => 'processed',
            'timestamp' => Carbon::now()->toIso8601ZuluString(),
            'external_id' => null,
        ]);

        $thirdPartyTransaction = ThirdPartyTransaction::query()->make([
            'transaction_id' => $sparkTransaction->id,
            'status' => 1,
            'description' => 'description',
        ]);
        $thirdPartyTransaction->manufacturerTransaction()->associate($sparkTransaction);
        $thirdPartyTransaction->save();

        $transaction = Transaction::query()->make([
            'amount' => 1000,
            'sender' => '905494322161',
            'message' => $customer->meters[0]->meter->serial_number,
            'type' => 'energy',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $transaction->originalTransaction()->associate($thirdPartyTransaction);
        $transaction->save();
    }

    private function initializeAdminData() {
        $user = factory(User::class)->create();
        $address = Address::query()->make([
            'phone' => '+905494322161',
            'is_primary' => 1,
            'owner_type' => 'admin',
        ]);
        $address->owner()->associate($user);
        $address->save();
    }

    private function addSmsSettings() {
        $smsSetting = SmSetting::query()->make();

        $smsTransaction = SmSmsSetting::query()->create([
            'state' => 'Transactions',
            'not_send_elder_than_mins' => 5,
        ]);

        $smsSetting->setting()->associate($smsTransaction);
        $smsSetting->save();

        $balanceSetting = SmSetting::query()->make();
        $smsLowBalanceWarning = SmSmsSetting::query()->create([
            'id' => 2,
            'state' => 'Low Balance Warning',
            'not_send_elder_than_mins' => 5,
            'enabled' => 1,
            'updated_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);
        $balanceSetting->setting()->associate($smsLowBalanceWarning);
        $balanceSetting->save();
    }

    private function addSyncSettings() {
        $minInterval = CarbonInterval::make('1minute');
        $now = Carbon::now();
        $siteSetting = SmSetting::query()->make();
        $syncSite = SmSyncSetting::query()->create([
            'action_name' => 'Sites',
            'sync_in_value_str' => 'minute',
            'sync_in_value_num' => 1,
            'max_attempts' => 2,
        ]);
        $siteSetting->setting()->associate($syncSite);
        $siteSetting->save();
        $syncAction = [
            'sync_setting_id' => $syncSite->id,
            'attempts' => 2,
            'next_sync' => $now->sub($minInterval),
        ];
        SmSyncAction::query()->create($syncAction);
    }

    private function addSmsBodies() {
        $bodies = [
            [
                'reference' => 'SmsTransactionHeader',
                'place_holder' => 'Dear [name] [surname], we received your transaction [transaction_amount].',
                'variables' => 'name,surname,transaction_amount',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SmsReminderHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SmsResendInformationHeader',
                'place_holder' => 'Dear [name] [surname], we received your resend last transaction information demand.',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'EnergyConfirmation',
                'place_holder' => 'Meter: [meter] , [token]  Unit [energy] .',
                'variables' => 'meter,token,energy',
                'title' => 'Meter Charge',
            ],
            [
                'reference' => 'AccessRateConfirmation',
                'place_holder' => 'Service Charge: [amount] ',
                'variables' => 'amount',
                'title' => 'Tariff Fixed Cost',
            ],
            [
                'reference' => 'AssetRateReminder',
                'place_holder' => 'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
                'variables' => 'appliance_type_name,remaining,due_date',
                'title' => 'Appliance Payment Reminder',
            ],
            [
                'reference' => 'AssetRatePayment',
                'place_holder' => 'Appliance:   [appliance_type_name]  [amount]',
                'variables' => 'appliance_type_name,amount',
                'title' => 'Appliance Payment',
            ],
            [
                'reference' => 'OverdueAssetRateReminder',
                'place_holder' => 'you forgot to pay the rate of [appliance_type_name] ( [remaining] )
                 on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
                'variables' => 'appliance_type_name,remaining,due_date',
                'title' => 'Overdue Appliance Payment Reminder',
            ],
            [
                'reference' => 'PricingDetails',
                'place_holder' => 'Transaction amount is [amount], \n VAT for energy :
                [vat_energy] \n VAT for the other staffs : [vat_others] . ',
                'variables' => 'amount,vat_energy,vat_others',
                'title' => 'Pricing Details',
            ],
            [
                'reference' => 'ResendInformation',
                'place_holder' => 'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
                'variables' => 'meter,token,energy,amount',
                'title' => 'Resend Last Transaction Information',
            ],
            [
                'reference' => 'ResendInformationLastTransactionNotFound',
                'place_holder' => 'Last transaction information not found for Meter: [meter]',
                'variables' => 'meter',
                'title' => 'Last Transaction Information Not Found',
            ],
            [
                'reference' => 'SmsReminderFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SmsTransactionFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SmsResendInformationFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        foreach ($bodies as $body) {
            SmsBody::query()->create([
                'reference' => $body['reference'],
                'place_holder' => $body['place_holder'],
                'body' => $body['place_holder'],
                'variables' => $body['variables'],
                'title' => $body['title'],
            ]);
        }
        $smsBodies = [
            [
                'reference' => 'SparkSmsLowBalanceHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsLowBalanceBody',
                'place_holder' => 'your credit balance has reduced under [low_balance_limit],
                 your currently balance is [credit_balance]',
                'variables' => 'low_balance_limit,credit_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SparkSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        collect($smsBodies)->each(function ($smsBody) {
            SmSmsBody::query()->create([
                'reference' => $smsBody['reference'],
                'place_holder' => $smsBody['place_holder'],
                'body' => $smsBody['place_holder'],
                'variables' => $smsBody['variables'],
                'title' => $smsBody['title'],
            ]);
        });

        return SmsBody::query()->get();
    }
}
