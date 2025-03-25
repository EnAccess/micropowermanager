<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use Carbon\Carbon;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTariffFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\MeterConsumptionFactory;
use Database\Factories\MeterTokenFactory;
use Database\Factories\PaymentHistoryFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeterTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $company;
    private $city;
    private $connectionType;
    private $connectionGroup;
    private $companyDatabase;
    private $manufacturer;
    private $meterType;
    private $meter;
    private $meterTariff;
    private $person;
    private $token;
    private $transaction;

    public function testUserGetsMeterList() {
        $this->createTestData();
        $meterCunt = 5;
        while ($meterCunt > 0) {
            $person = PersonFactory::new()->create();
            $meter = MeterFactory::new()->create([
                'meter_type_id' => $this->meterType->id,
                'in_use' => true,
                'manufacturer_id' => 1,
                'serial_number' => str_random(36),
            ]);

            --$meterCunt;
        }
        $response = $this->actingAs($this->user)->get('/api/meters');
        $response->assertStatus(200);
        $this->assertEquals(5, count($response['data']));
    }

    public function testUserCreatesAMeter() {
        $this->createTestData();
        $meterData = [
            'serial_number' => '123456789',
            'meter_type_id' => $this->meterType->id,
            'in_use' => false,
            'manufacturer_id' => $this->manufacturer->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/meters', $meterData);
        $response->assertStatus(201);
        $meter = Meter::query()->latest('id')->first();
        $this->assertEquals($meter->serial_number, $meterData['serial_number']);
    }

    public function testUserGetsMeterBySerialNumber() {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf('/api/meters/%s', $meter->serial_number));
        $response->assertStatus(200);
        $this->assertEquals($meter->serial_number, $response['data']['serial_number']);
    }

    public function testUserSearchesMetersBySerialNumber() {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf('/api/meters/search?term=%s', $meter->serial_number));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['id'], $meter->id);
    }

    public function testUserSearchesMeterByTariffName() {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/search?term=%s',
            $this->meterTariff->name
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['id'], $meter->id);
    }

    public function testUserUpdatesMetersGeolocation() {
        $this->createMeterWithGeo();
        $meterData = [
            ['lat' => '444', 'lng' => '555', 'id' => 1],
            ['lat' => '666', 'lng' => '777', 'id' => 2],
        ];
        $response = $this->actingAs($this->user)->put('/api/meters', $meterData);
        $response->assertStatus(200);
    }

    public function testUserGetsPersonMeters() {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/people/%s/meters',
            $this->person->id
        ));
        $response->assertStatus(200);
        $metersCount = Meter::query()->get()->count();
        $this->assertEquals(count($response['data']['meters']), $metersCount);
    }

    public function testUserGetsPersonMetersGeographicalInformation() {
        $this->createMeterWithGeo();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/people/%s/meters/geo',
            $this->person->id
        ));
        $response->assertStatus(200);
    }

    public function testUserGetsMetersTransactions() {
        $meter = $this->createMeterWithTransaction();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/%s/transactions',
            $meter->serial_number
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['amount'], $this->transaction->amount);
        $this->assertEquals($response['data'][0]['id'], $this->transaction->id);
    }

    public function testUserGetsMeterRevenueBySerialNumber() {
        $meter = $this->createMeterWithTransaction();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/%s/revenue',
            $meter->serial_number
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['revenue'], $this->transaction->amount);
    }

    public function testUserGetsMeterConsumptions() {
        $meter = $this->createMeterWithTransaction();
        $consumption = MeterConsumptionFactory::new()->create();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/%s/consumptions/%s/%s',
            $meter->serial_number,
            Carbon::now()->subDays(3)->format('Y-m-d'),
            Carbon::now()->format('Y-m-d')
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['consumption'], $consumption->consumption);
        $this->assertEquals($response['data'][0]['meter_id'], $meter->id);
    }

    public function testUserDeletesAMeter() {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->delete(sprintf('/api/meters/%s', $meter->id));
        $response->assertStatus(204);
    }

    protected function createTestData() {
        $this->user = UserFactory::new()->create();
        $this->city = CityFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();
        $this->manufacturer = ManufacturerFactory::new()->create();
        $this->meterType = MeterTypeFactory::new()->create();
        $this->meterTariff = MeterTariffFactory::new()->create();
        $this->connectionType = ConnectionTypeFactory::new()->create();
        $this->connectionGroup = ConnectionTypeFactory::new()->create();
        $this->person = PersonFactory::new()->create();
    }

    protected function getMeter(): mixed {
        $this->createTestData();
        $meter = MeterFactory::new()->create([
            'meter_type_id' => $this->meterType->id,
            'in_use' => true,
            'manufacturer_id' => $this->manufacturer->id,
            'serial_number' => str_random(36),
        ]);

        return $meter;
    }

    protected function createMeterWithGeo(): void {
        $this->createTestData();
        $meterCunt = 2;
        while ($meterCunt > 0) {
            $meter = MeterFactory::new()->create([
                'meter_type_id' => $this->meterType->id,
                'in_use' => true,
                'manufacturer_id' => 1,
                'serial_number' => str_random(36),
            ]);
            $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
            $this->person = PersonFactory::new()->create();
            $addressData = [
                'city_id' => $this->city->id,
                'geo_id' => $geographicalInformation->id,
            ];

            $address = Address::query()->make([
                'email' => isset($addressData['email']) ?: null,
                'phone' => isset($addressData['phone']) ?: null,
                'street' => isset($addressData['street']) ?: null,
                'city_id' => isset($addressData['city_id']) ?: null,
                'geo_id' => isset($addressData['geo_id']) ?: null,
                'is_primary' => isset($addressData['is_primary']) ?: 0,
            ]);
            $address->owner()->associate($meter)->save();
            $geographicalInformation->owner()->associate($meter->device()->person)->save();
            --$meterCunt;
        }
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    protected function createMeterWithTransaction() {
        $meter = $this->getMeter();
        $this->transaction = TransactionFactory::new()->create([
            'id' => 1,
            'amount' => $this->faker->unique()->randomNumber(),
            'sender' => $this->faker->phoneNumber,
            'message' => $meter->serial_number,
            'original_transaction_id' => $this->faker->unique()->randomNumber(),
            'original_transaction_type' => 'agent_transaction',
        ]);
        $this->token = MeterTokenFactory::new()->create([
            'meter_id' => $meter->id,
            'token' => $this->faker->unique()->randomNumber(),
        ]);
        $paymentHistory = PaymentHistoryFactory::new()->create([
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'payment_service' => 'agent_transaction',
            'sender' => $this->faker->phoneNumber,
            'payment_type' => 'energy',
            'paid_for_type' => 'token',
            'paid_for_id' => $this->token->id,
            'payer_type' => 'person',
            'payer_id' => $this->person->id,
        ]);

        return $meter;
    }
}
