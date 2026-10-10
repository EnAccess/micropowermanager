<?php

namespace App\Plugins\SparkMeter\Tests\Feature;

use App\Models\Address\Address;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\Tariff;
use App\Models\User;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Models\SmSmsBody;
use App\Plugins\SparkMeter\Models\SmSmsFeedbackWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendSmsTest extends TestCase {
    use RefreshDatabase;

    /** @test */
    public function isMeterResetFeedbackSend(): void {
        Queue::fake();
        $this->withoutExceptionHandling();
        $person = $this->initializeData()['customer'];
        $user = User::factory()->createOne();

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
    public function isMeterBalanceFeedbackSend(): void {
        Queue::fake();
        $this->withoutExceptionHandling();
        $person = $this->initializeData()['customer'];
        $user = User::factory()->createOne();
        $data = [
            'sender' => $person->addresses[0]->phone,
            'message' => 'Balance',
        ];
        $response = $this->actingAs($user)->post('/api/sms', $data);
        // SmsController::store() always returns a freshly-created Sms resource, and
        // Laravel's JsonResource reports 201 whenever the underlying model
        // wasRecentlyCreated -- so this is 201 regardless of feedback message type.
        $response->assertStatus(201);
        $smsCount = Sms::query()->first()->count();
        $this->assertEquals(2, $smsCount);
    }

    /**
     * @return array<string, mixed>
     */
    private function initializeData(): array {
        $this->addSmsBodies();
        $this->addFeedBackKeys();
        MainSettings::factory()->createOne();

        // create person
        Person::factory()->createOne();
        // create meter-tariff
        Tariff::factory()->createOne();

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
        $meter = Meter::query()->create([
            'serial_number' => 'SM15R-01-000002F9',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
            'tariff_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
        ]);

        // associate meter with a person
        $p = Person::query()->first();
        Device::query()->create([
            'person_id' => $p->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
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

    /**
     * The generic (non-Spark) SmsBody rows the SparkMeter senders lean on indirectly
     * (e.g. via SmsService's other configs) are already seeded by the
     * `create_sms_bodies` tenant migration; only the Spark-specific SmSmsBody rows
     * consumed by SparkSmsConfig's senders need to be added here.
     */
    private function addSmsBodies(): void {
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
        collect($smsBodies)->each(function (array $smsBody) {
            SmSmsBody::query()->create([
                'reference' => $smsBody['reference'],
                'place_holder' => $smsBody['place_holder'],
                'body' => $smsBody['place_holder'],
                'variables' => $smsBody['variables'],
                'title' => $smsBody['title'],
            ]);
        });
    }

    private function addFeedBackKeys(): void {
        SmSmsFeedbackWord::query()->create([
            'meter_reset' => 'Reset',
            'meter_balance' => 'Balance',
        ]);
    }
}
