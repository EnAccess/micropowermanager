<?php

namespace Inensus\SparkMeter\Tests\Feature;

use App\Models\Address\Address;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\SmsBody;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SmSmsBody;
use Inensus\SparkMeter\Models\SmSmsFeedbackWord;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SendSms extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function isMeterResetFeedbackSend() {
        Queue::fake();
        $this->withoutExceptionHandling();
        $person = $this->initializeData()['customer'];
        $user = factory(User::class)->create();

        $data = [
            'sender' => $person->addresses[0]->phone,
            'message' => 'Reset',
        ];
        $response = $this->actingAs($user)->post('/api/sms', $data);
        $response->assertStatus(201);
        $smsCount = Sms::query()->first()->count();
        $this->assertEquals(2, $smsCount);
    }

    /** @test */
    public function isMeterBalanceFeedbackSend() {
        Queue::fake();
        $this->withoutExceptionHandling();
        $person = $this->initializeData()['customer'];
        $user = factory(User::class)->create();
        $data = [
            'sender' => $person->addresses[0]->phone,
            'message' => 'Balance',
        ];
        $response = $this->actingAs($user)->post('/api/sms', $data);
        $response->assertStatus(200);
        $smsCount = Sms::query()->first()->count();
        $this->assertEquals(2, $smsCount);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    private function initializeData() {
        $this->addSmsBodies();
        $this->addFeedBackKeys();
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
            'serial_number' => 'SM15R-01-000002F9',
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

        SmSite::query()->create([
            'site_id' => '1',
            'mpm_mini_grid_id' => '1',
            'thundercloud_url' => 'http://sparkapp-staging.spk.io:5010/api/v0',
            'thundercloud_token' => '.eJwNw0EOgDAIBMC_cJbEhZbCW4yHbbX_f4JOMpfY4tjvGJonqM25NZNTmVb570STQx728h2zwZBnLloVyj2iFhwh9wfYAhKT.X2sZTg.JthNGNnFRaqEPqRJW8okuhzkucE',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);

        SmCustomer::query()->create([
            'site_id' => '1',
            'customer_id' => '6e70b116-4d0e-4975-b8f7-a948138cfa67',
            'mpm_customer_id' => $p->id,
            'credit_balance' => 100,
            'low_balance_limit' => 150,
            'hash' => 'xxxxxxxxx',
        ]);

        return ['customer' => $p];
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
                'place_holder' => 'your credit balance has reduced under [low_balance_limit],'
                    .'your currently balance is [credit_balance]',
                'variables' => 'low_balance_limit,credit_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackBody',
                'place_holder' => 'your currently balance is [credit_balance]',
                'variables' => 'credit_balance',
                'title' => 'Balance Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackBody',
                'place_holder' => 'your meter, [meter_serial] has reset successfully.',
                'variables' => 'meter_serial',
                'title' => 'Meter Reset Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFailedFeedbackBody',
                'place_holder' => 'meter reset failed with [meter_serial].',
                'variables' => 'meter_serial',
                'title' => 'Meter Reset Failed Feedback',
            ],
            [
                'reference' => 'SparkSmsMeterResetFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SparkSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SparkSmsBalanceFeedbackFooter',
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

    private function addFeedBackKeys() {
        SmSmsFeedbackWord::query()->create([
            'meter_reset' => 'Reset',
            'meter_balance' => 'Balance',
        ]);
    }
}
