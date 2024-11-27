<?php

namespace Inensus\KelinMeter\Tests\Feature;

use App\Jobs\TokenProcessor;
use App\Misc\TransactionDataContainer;
use App\Models\AccessRate\AccessRate;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterToken;
use App\Models\Meter\MeterType;
use App\Models\SmsAndroidSetting;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use Carbon\Carbon;
use Database\Factories\PersonFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Inensus\KelinMeter\Models\KelinCredential;
use Inensus\KelinMeter\Models\KelinMeter;
use Tests\TestCase;

class RechargeMeterTest extends TestCase {
    use RefreshDatabase;
    use WithFaker;

    public function testTokenGeneration() {
        Queue::fake();
        $this->initData();
        $t = Transaction::create([
            'original_transaction_id' => 1,
            'original_transaction_type' => 'vodacom_transaction',
            'amount' => 700,
            'sender' => '+132456789',
            'message' => '0124-8810-1080-3',
        ]);
        $transactionData = TransactionDataContainer::initialize($t);
        $transactionData = \App\PaymentHandler\AccessRate::payAccessRate($transactionData);
        $kWhToBeCharged = 0.0;
        $transactionData->chargedEnergy = round($kWhToBeCharged, 1);
        TokenProcessor::dispatchNow($transactionData);
        $this->assertEquals(1, MeterToken::query()->get()->count());
    }

    private function initData() {
        KelinCredential::query()->create([
            'id' => 1,
            'api_url' => 'http://222.222.60.178:62189/EI',
            'username' => 'konexa',
            'password' => 123456,
            'authentication_token' => '0C434321FFC0E0EC7FBBA1C6C5A1B09F',
            'is_authenticated' => 1,
        ]);
        $tariff = MeterTariff::query()->create([
            'id' => 1,
            'name' => 'Economy',
            'price' => 20000,
            'currency' => 'a',
            'total_price' => 20000,
        ]);
        AccessRate::create(
            [
                'tariff_id' => 1,
                'amount' => 2000,
                'period' => 7,
                'stack' => 1,
            ]
        );
        MeterType::create([
            'online' => 0,
            'phase' => 1,
            'max_current' => 10,
        ]);
        Manufacturer::create([
            'name' => 'Kelin Meters',
            'website' => '',
            'api_name' => 'KelinMeterApi',
        ]);

        $meter = Meter::create([
            'serial_number' => '0124-8810-1080-3',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
        ]);
        KelinMeter::query()->create([
            'mpm_meter_id' => 1,
            'meter_address' => '012488101080',
            'meter_name' => '012488101080',
            'customer_no' => 'tests',
            'rtuId' => 1,
            'hash' => 'test',
        ]);
        $p = PersonFactory::new()->create();
        $p->meters()->create([
            'tariff_id' => 1,
            'meter_id' => 1,
            'connection_type_id' => 1,
            'connection_group_id' => 1,
        ]);
        $meterParameter = new MeterParameter();
        $meterParameter->tariff()->associate($tariff);
        $meterParameter->owner_type = 'person';
        $meterParameter->owner_id = 1;
        $meterParameter->connection_type_id = 1;
        $meterParameter->connection_group_id = 1;
        $meterParameter->meter_id = 1;
        $meter->in_use = 1;
        $meter->save();
        $meterParameter->save();
        SmsAndroidSetting::query()->create([
            'key' => 'test-Key',
            'token' => 'test-token',
            'name' => 'test',
            'url' => 'test',
            'callback' => 'test',
        ]);
        VodacomTransaction::query()->create([
            'conversation_id' => 'a',
            'originator_conversation_id' => 'b',
            'mpesa_receipt' => 'c',
            'transaction_date' => Carbon::now(),
            'transaction_id' => 1,
        ]);
    }
}
