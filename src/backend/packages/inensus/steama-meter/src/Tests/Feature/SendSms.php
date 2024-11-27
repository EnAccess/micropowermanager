<?php

namespace Inensus\SteamaMeter\Tests\Feature;

use App\Models\Address\Address;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\SmsBody;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaSite;
use Inensus\SteamaMeter\Models\SteamaSmsBody;
use Inensus\SteamaMeter\Models\SteamaSmsFeedbackWord;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SendSms extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function isMeterBalanceFeedbackSend() {
        Queue::fake();
        Config::set('app.debug', false);
        $this->withoutExceptionHandling();
        $person = $this->initializeData()['customer'];
        $user = factory(User::class)->create();
        $data = [
            'sender' => $person->addresses[0]->phone,
            'message' => 'Balance',
        ];
        $response = $this->actingAs($user)->post('/api/sms', $data);
        $response->assertStatus(201);
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

        // associate meter with a person
        $p = Person::query()->first();

        // associate address with a person
        $address = Address::query()->make([
            'phone' => '+905494322161',
            'is_primary' => 1,
            'owner_type' => 'person',
        ]);
        $address->owner()->associate($p);
        $address->save();

        SteamaSite::query()->create([
            'site_id' => 1,
            'mpm_mini_grid_id' => 1,
        ]);

        SteamaCustomer::query()->create([
            'site_id' => 1,
            'customer_id' => 1,
            'user_type_id' => 1,
            'mpm_customer_id' => $p->id,
            'energy_price' => 100,
            'account_balance' => 1000,
            'low_balance_warning' => 150,
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
                'reference' => 'SteamaSmsLowBalanceHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SteamaSmsLowBalanceBody',
                'place_holder' => 'your credit balance has reduced under [low_balance_warning],'
                    .'your currently balance is [account_balance]',
                'variables' => 'low_balance_warning,account_balance',
                'title' => 'Low Balance Limit Notify',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackHeader',
                'place_holder' => 'Dear [name] [surname],',
                'variables' => 'name,surname',
                'title' => 'Sms Header',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackBody',
                'place_holder' => 'your currently balance is [account_balance]',
                'variables' => 'account_balance',
                'title' => 'Balance Feedback',
            ],
            [
                'reference' => 'SteamaSmsBalanceFeedbackFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
            [
                'reference' => 'SteamaSmsLowBalanceFooter',
                'place_holder' => 'Your Company etc.',
                'variables' => '',
                'title' => 'Sms Footer',
            ],
        ];
        collect($smsBodies)->each(function ($smsBody) {
            SteamaSmsBody::query()->create([
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
        SteamaSmsFeedbackWord::query()->create([
            'meter_balance' => 'Balance',
        ]);
    }
}
