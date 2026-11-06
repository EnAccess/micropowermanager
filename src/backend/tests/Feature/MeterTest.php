<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\DeviceFactory;
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

    public function testUserGetsNewestMetersFirst(): void {
        $this->createTestData();
        $older = MeterFactory::new()->create(['meter_type_id' => $this->meterType->id, 'manufacturer_id' => 1, 'serial_number' => 'OLDER-METER']);
        $newer = MeterFactory::new()->create(['meter_type_id' => $this->meterType->id, 'manufacturer_id' => 1, 'serial_number' => 'NEWER-METER']);

        $response = $this->actingAs($this->user)->get('/api/meters');

        $response->assertStatus(200);
        $listedIds = array_column($response['data'], 'id');
        $this->assertLessThan(array_search($older->id, $listedIds, true), array_search($newer->id, $listedIds, true));
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

    public function testUserGetsMetersTransactionsIncludingAdHocTokens(): void {
        $this->createTestData();
        $device = $this->meterDevice('TRANSACTIONS-METER');
        $otherDevice = $this->meterDevice('OTHER-METER');
        $payment = $this->transactionWithToken($device, Transaction::TYPE_ENERGY, now()->subHour());
        $adHoc = $this->transactionWithToken($device, Transaction::TYPE_AD_HOC, now());
        $this->transactionWithToken($otherDevice, Transaction::TYPE_ENERGY, now());

        $response = $this->actingAs($this->user)->get('/api/meters/TRANSACTIONS-METER/transactions');

        $response->assertStatus(200);
        $this->assertSame([$adHoc->id, $payment->id], array_column($response['data'], 'id'));
        $response->assertJsonPath('data.0.type', Transaction::TYPE_AD_HOC);
        $response->assertJsonPath('data.0.token.token', $adHoc->token->token);
        $response->assertJsonPath('data.1.token.token_unit', Token::UNIT_KWH);
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
            $geographicalInformation = GeographicalInformation::query()->make(['geo_json' => GeographicalInformation::pointFromString('111,222')]);
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
            'sender' => $this->faker->e164PhoneNumber(),
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
            'sender' => $this->faker->e164PhoneNumber(),
            'payment_type' => 'energy',
            'paid_for_type' => 'token',
            'paid_for_id' => $this->token->id,
            'payer_type' => 'person',
            'payer_id' => $this->person->id,
        ]);

        return $meter;
    }

    private function meterDevice(string $serialNumber): Device {
        $meter = MeterFactory::new()->create([
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'manufacturer_id' => ManufacturerFactory::new()->create()->id,
            'serial_number' => $serialNumber,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $serialNumber,
        ]);
    }

    private function transactionWithToken(Device $device, string $type, Carbon $createdAt): Transaction {
        $transaction = TransactionFactory::new()->create([
            'type' => $type,
            'message' => $device->device_serial,
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => $this->faker->unique()->randomNumber(),
            'created_at' => $createdAt,
        ]);
        TokenFactory::new()->create(['transaction_id' => $transaction->id, 'device_id' => $device->id]);

        return $transaction->load('token');
    }
}
