<?php

namespace Inensus\MesombPaymentProvider\Tests\Feature;

use App\Models\AccessRate\AccessRate;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Address\Address;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidationTests extends TestCase {
    use RefreshDatabase;

    public function testOnlyAuthenticatedSwiftaUserSendsTransaction() {
        $data = [
            'meter_number' => '478881899',
            'amount' => 1000,
            'cipher' => '549401e2bd56c9bee49737d16ccf58b1',
            'timestamp' => '1123123',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $response = $this->actingAsWrong($user)->post('/api/swifta/validation', $data)->assertStatus(401);
        $response->assertJson([
            'success' => 0,
            'message' => 'Authentication field.',
        ]);
    }

    public function testWithoutInvalidCipher() {
        $data = [
            'meter_number' => '478881899',
            'amount' => 1000,
            'cipher' => 'test',
            'timestamp' => '111111111',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $response = $this->actingAs($user)->post('/api/swifta/validation', $data)->assertStatus(400);
        $response->assertJson([
            'success' => 0,
            'message' => 'cipher validation field.',
        ]);
    }

    public function testWithoutNonExistingMeterNumber() {
        $this->initializeData();
        $data = [
            'meter_number' => '4700005610',
            'amount' => 1000,
            'cipher' => 'babd6f099ab7ac7e4ea42b5d298018ed',
            'timestamp' => '111111111',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);

        $response = $this->actingAs($user)->post('/api/swifta/validation', $data)->assertStatus(400);
        $response->assertJson([
            'success' => 0,
            'message' => 'meter_number validation field.',
        ]);
    }

    public function testWithNonSenderAddress() {
        $this->initializeData();
        Address::query()->first()->delete();
        $data = [
            'meter_number' => '4700005646',
            'amount' => 500,
            'cipher' => '8ce10af5a71b0243041bf8d30fbbc653',
            'timestamp' => '111111111',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);

        $response = $this->actingAs($user)->post('/api/swifta/validation', $data)->assertStatus(400);

        $response->assertJson([
            'success' => 0,
            'message' => 'No phone number record found by customer.',
        ]);
    }

    public function testWithInvalidTransactionAmount() {
        $this->initializeData();
        AccessRate::query()->where('id', 1)->update([
            'tariff_id' => 1,
            'amount' => 1000,
            'period' => 7,
        ]);
        AccessRatePayment::query()->create([
            'meter_id' => 1,
            'access_rate_id' => 1,
            'due_date' => Carbon::now()->addDays(-1),
            'debt' => 1000,
        ]);
        $data = [
            'meter_number' => '4700005646',
            'amount' => 500,
            'cipher' => '8ce10af5a71b0243041bf8d30fbbc653',
            'timestamp' => '111111111',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $response = $this->actingAs($user)->post('/api/swifta/validation', $data)->assertStatus(400);
        $response->assertJson([
            'success' => 0,
            'message' => 'Amount validation field.',
        ]);
    }

    public function testWithValidTransactionAmount() {
        $this->initializeData();
        $data = [
            'meter_number' => '4700005646',
            'amount' => 500,
            'cipher' => '8ce10af5a71b0243041bf8d30fbbc653',
            'timestamp' => '111111111',
        ];
        $user = User::query()->create([
            'name' => 'test',
            'password' => '123456',
            'email' => 'test',
        ]);
        $response = $this->actingAs($user)->post('/api/swifta/validation', $data)->assertStatus(200);
        $swiftaTransactionsCount = SwiftaTransaction::query()->count();
        $this->assertEquals(1, $swiftaTransactionsCount);
        $this->assertEquals(-2, SwiftaTransaction::query()->first()->status);
        $person = Person::query()->first();
        $response->assertJson([
            'success' => 1,
            'amount' => $data['amount'],
            'cipher' => $data['cipher'],
            'timestamp' => $data['timestamp'],
            'transaction_id' => 1,
            'customer' => $person->name.' '.$person->surname,
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

    public function actingAsWrong($user, $driver = null) {
        $customClaims = ['usr' => 'swifta-token-wrong', 'exp' => Carbon::now()->addYears(1)->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    public function actingAs($user, $driver = null) {
        $customClaims = ['usr' => 'swifta-token', 'exp' => Carbon::now()->addYears(1)->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
