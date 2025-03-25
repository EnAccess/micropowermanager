<?php

namespace Inensus\MesombPaymentProvider\Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransactionTests extends TestCase {
    use RefreshDatabase;

    public function testWithNonExistingTransaction() {
        $data = [
            'transaction_id' => -1,
            'transaction_reference' => 'ref',
            'amount' => 1000,
            'cipher' => '549401e2bd56c9bee49737d16ccf58b1',
            'timestamp' => '1123123',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $response = $this->actingAs($user)->post('/api/swifta/transaction', $data)->assertStatus(400);
        $response->assertJson([
            'success' => 0,
            'message' => 'transaction_id validation field.',
        ]);
    }

    public function testWithDifferentTransactionAmountFromValidatedTransaction() {
        $this->initializeData();
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $data = [
            'meter_number' => '4700005646',
            'amount' => 500,
            'cipher' => '8ce10af5a71b0243041bf8d30fbbc653',
            'timestamp' => '111111111',
        ];
        $this->actingAs($user)->post('/api/swifta/validation', $data);
        $data = [
            'transaction_id' => 1,
            'transaction_reference' => 'ref',
            'amount' => 1000,
            'cipher' => '549401e2bd56c9bee49737d16ccf58b1',
            'timestamp' => '1123123',
        ];

        $response = $this->actingAs($user)->post('/api/swifta/transaction', $data)->assertStatus(400);
        $response->assertJson([
            'success' => 0,
            'message' => 'amount validation field.',
        ]);
    }

    public function testWithValidTransaction() {
        Queue::fake();
        $this->initializeData();
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $data = [
            'meter_number' => '4700005646',
            'amount' => 500,
            'cipher' => '8ce10af5a71b0243041bf8d30fbbc653',
            'timestamp' => '111111111',
        ];
        $this->actingAs($user)->post('/api/swifta/validation', $data);
        $data = [
            'transaction_id' => 1,
            'transaction_reference' => 'ref',
            'amount' => 500,
            'cipher' => '8ce10af5a71b0243041bf8d30fbbc653',
            'timestamp' => '111111111',
        ];

        $response = $this->actingAs($user)->post('/api/swifta/transaction', $data)->assertStatus(201);
        $swiftaTransactions = SwiftaTransaction::query()->get();
        $swiftaTransaction = $swiftaTransactions->first();
        $transactions = Transaction::query()->get();
        $this->assertEquals(1, $transactions->count());
        $this->assertEquals(1, $swiftaTransactions->count());
        $this->assertEquals(0, $swiftaTransaction->status);
        Queue::assertPushed(ProcessPayment::class);
        $response->assertJson([
            'success' => 1,
            'amount' => $data['amount'],
            'cipher' => $data['cipher'],
            'timestamp' => $data['timestamp'],
            'transaction_id' => $data['transaction_id'],
        ]);
    }

    private function initializeData() {
        // create person
        Person::factory()->create();
        // create meter-tariff
        $tariff = MeterTariff::query()->create([
            'name' => 'test tariff',
            'price' => 100000,
            'total_price' => 100000,
            'currency' => 'TZS',
        ]);
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
            'serial_number' => '4700005646',
            'meter_type_id' => 1,
            'in_use' => 1,
            'manufacturer_id' => 1,
        ]);

        // associate meter with a person
        $p = Person::query()->first();

        $address = Address::query()->make([
            'phone' => '237400001019',
            'is_primary' => 1,
            'owner_type' => 'person',
        ]);
        $address->owner()->associate($p);
        $address->save();
    }

    public function actingAs($user, $driver = null) {
        $customClaims = ['usr' => 'swifta-token', 'exp' => Carbon::now()->addYears(1)->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
