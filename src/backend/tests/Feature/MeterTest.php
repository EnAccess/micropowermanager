<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use Carbon\Carbon;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterConsumptionFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\PaymentHistoryFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class MeterTest extends TestCase {
    use CreateEnvironments;

    private $user;
    private $manufacturer;
    private $meterType;
    private $meter;
    private $meterTariff;
    private $person;
    private $token;
    private $transaction;

    public function testUserGetsMeterList(): void {
        $this->createTestData();
        $meterCunt = 5;
        while ($meterCunt > 0) {
            MeterFactory::new()->create([
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

    public function testUserCreatesAMeter(): void {
        $this->createTestData();
        $meterData = [
            'serial_number' => '123456789',
            'meter_type_id' => $this->meterType->id,
            'in_use' => false,
            'manufacturer_id' => $this->manufacturer->id,
            'connection_type_id' => $this->connectionType->id,
            'connection_group_id' => $this->connectionGroup->id,
            'tariff_id' => $this->meterTariff->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/meters', $meterData);
        $response->assertStatus(201);
        $meter = Meter::query()->latest('id')->first();
        $this->assertEquals($meter->serial_number, $meterData['serial_number']);
    }

    public function testUserGetsMeterBySerialNumber(): void {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf('/api/meters/%s', $meter->serial_number));
        $response->assertStatus(200);
        $this->assertEquals($meter->serial_number, $response['data']['serial_number']);
    }

    public function testUserSearchesMetersBySerialNumber(): void {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf('/api/meters/search?term=%s', $meter->serial_number));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['id'], $meter->id);
    }

    public function testUserSearchesMeterByTariffName(): void {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/search?term=%s',
            $this->meterTariff->name
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['id'], $meter->id);
    }

    public function testUserUpdatesMetersGeolocation(): void {
        $this->createMeterWithGeo();
        $meterData = [
            ['lat' => '444', 'lng' => '555', 'id' => 1],
            ['lat' => '666', 'lng' => '777', 'id' => 2],
        ];
        $response = $this->actingAs($this->user)->put('/api/meters', $meterData);
        $response->assertStatus(200);
    }

    public function testUserGetsPersonMeters(): void {
        $this->meter = $this->getMeter();
        $this->createMeterDevice($this->person);
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/people/%s/meters',
            $this->person->id
        ));
        $response->assertStatus(200);
        $metersCount = Meter::query()->get()->count();
        $this->assertEquals(count($response['data']['devices']), $metersCount);
    }

    public function testUserGetsPersonMetersGeographicalInformation(): void {
        $this->createMeterWithGeo();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/people/%s/meters/geo',
            $this->person->id
        ));
        $response->assertStatus(200);
    }

    public function testUserGetsMetersTransactions(): void {
        $meter = $this->createMeterWithTransaction();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/%s/transactions',
            $meter->serial_number
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['amount'], $this->transaction->amount);
        $this->assertEquals($response['data'][0]['id'], $this->transaction->id);
    }

    public function testUserGetsMeterRevenueBySerialNumber(): void {
        $meter = $this->createMeterWithTransaction();
        $response = $this->actingAs($this->user)->get(sprintf(
            '/api/meters/%s/revenue',
            $meter->serial_number
        ));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['revenue'], $this->transaction->amount);
    }

    public function testUserGetsMeterConsumptions(): void {
        $meter = $this->createMeterWithTransaction();
        $consumption = MeterConsumptionFactory::new()->create(['meter_id' => $meter->id]);
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

    public function testUserDeletesAMeter(): void {
        $meter = $this->getMeter();
        $response = $this->actingAs($this->user)->delete(sprintf('/api/meters/%s', $meter->id));
        $response->assertStatus(204);
    }

    protected function createTestData() {
        $this->user = UserFactory::new()->create();
        $this->user->syncRoles('admin');
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createCity(1);
        $this->manufacturer = ManufacturerFactory::new()->create();
        $this->meterType = MeterTypeFactory::new()->create();
        $this->meterTariff = TariffFactory::new()->create();
        $this->connectionType = ConnectionTypeFactory::new()->create();
        $this->connectionGroup = ConnectionTypeFactory::new()->create();
        $this->person = PersonFactory::new()->create();
    }

    protected function getMeter(): mixed {
        $this->createTestData();

        return MeterFactory::new()->create([
            'meter_type_id' => $this->meterType->id,
            'in_use' => true,
            'manufacturer_id' => $this->manufacturer->id,
            'serial_number' => str_random(36),
            'connection_type_id' => $this->connectionType->id,
            'connection_group_id' => $this->connectionGroup->id,
            'tariff_id' => $this->meterTariff->id,
        ]);
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
            $person = PersonFactory::new()->create();
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
            $geographicalInformation->owner()->associate($person)->save();
            --$meterCunt;
        }
    }

    protected function createMeterWithTransaction() {
        $meter = $this->getMeter();
        $this->meter = $meter;
        $this->createMeterDevice($this->person);
        $this->transaction = TransactionFactory::new()->create([
            'id' => 1,
            'amount' => $this->faker->unique()->randomNumber(),
            'sender' => $this->faker->phoneNumber(),
            'message' => $meter->serial_number,
            'original_transaction_id' => $this->faker->unique()->randomNumber(),
            'original_transaction_type' => 'agent_transaction',
        ]);
        $this->token = TokenFactory::new()->create([
            'device_id' => $this->meterDevice->id,
            'token' => $this->faker->unique()->randomNumber(),
            'transaction_id' => $this->transaction->id,
        ]);
        PaymentHistoryFactory::new()->create([
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'payment_service' => 'agent_transaction',
            'sender' => $this->faker->phoneNumber(),
            'payment_type' => 'energy',
            'paid_for_type' => 'token',
            'paid_for_id' => $this->token->id,
            'payer_type' => 'person',
            'payer_id' => $this->person->id,
        ]);

        return $meter;
    }
}
