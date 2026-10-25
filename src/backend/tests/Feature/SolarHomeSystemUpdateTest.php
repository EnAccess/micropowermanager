<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\VerifyDeviceMappingJob;
use App\Models\Appliance;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\GeographicalInformationFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\SolarHomeSystemFactory;
use Database\Factories\TokenFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class SolarHomeSystemUpdateTest extends TestCase {
    use CreateEnvironments;

    public function testItUpdatesManufacturerAndAppliance(): void {
        $this->createTestData();
        [$solarHomeSystem, $newManufacturer, $newAppliance] = $this->seedSolarHomeSystem();

        $response = $this->actingAs($this->user)->put(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['manufacturer_id' => $newManufacturer->id, 'appliance_id' => $newAppliance->id]
        );

        $response->assertStatus(200);
        $solarHomeSystem->refresh();
        $this->assertSame($newManufacturer->id, $solarHomeSystem->manufacturer_id);
        $this->assertSame($newAppliance->id, $solarHomeSystem->appliance_id);
    }

    public function testItValidatesManufacturerAndApplianceExist(): void {
        $this->createTestData();
        [$solarHomeSystem] = $this->seedSolarHomeSystem();

        $response = $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['manufacturer_id' => 999999, 'appliance_id' => 999999]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['manufacturer_id', 'appliance_id']);
    }

    public function testItReturns404WhenSolarHomeSystemMissing(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $appliance = $this->createAppliance();

        $response = $this->actingAs($this->user)->putJson(
            '/api/solar-home-systems/999999',
            ['manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        );

        $response->assertStatus(404);
    }

    public function testItChangesSerialNumberOfSolarHomeSystemAndDevice(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);

        $response = $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['serial_number' => 'SHS-NEW-0001', 'manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        );

        $response->assertStatus(200);
        $this->assertSame('SHS-NEW-0001', $solarHomeSystem->refresh()->serial_number);
        $this->assertSame('SHS-NEW-0001', $device->refresh()->device_serial);
    }

    public function testItVerifiesManufacturerMappingAfterSerialNumberChange(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        Queue::fake();

        $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['serial_number' => 'SHS-NEW-0001', 'manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        )->assertStatus(200);

        Queue::assertPushed(VerifyDeviceMappingJob::class);
        $this->assertSame('SHS-NEW-0001', $device->refresh()->device_serial);
    }

    public function testItKeepsUnchangedSerialNumberWhenUpdatingOtherFields(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => 1,
            'message' => $device->device_serial,
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['serial_number' => $solarHomeSystem->serial_number, 'manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        );

        $response->assertStatus(200);
        $this->assertSame($manufacturer->id, $solarHomeSystem->refresh()->manufacturer_id);
    }

    public function testItRejectsSerialNumberUsedByAnotherDevice(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $this->attachDevice($solarHomeSystem);
        [$otherSolarHomeSystem] = $this->seedSolarHomeSystem();
        $otherDevice = $this->attachDevice($otherSolarHomeSystem);

        $response = $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['serial_number' => $otherDevice->device_serial, 'manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['serial_number']);
    }

    public function testItRejectsSerialNumberChangeWhenDeviceHasTransactions(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => 1,
            'message' => $device->device_serial,
        ]);

        $this->assertSerialNumberChangeIsRejected($solarHomeSystem, $device, $manufacturer, $appliance);
    }

    public function testItRejectsSerialNumberChangeWhenDeviceHasTokens(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        TokenFactory::new()->create(['device_id' => $device->id]);

        $this->assertSerialNumberChangeIsRejected($solarHomeSystem, $device, $manufacturer, $appliance);
    }

    public function testItRejectsSerialNumberChangeWhenDeviceIsSold(): void {
        $this->createTestData();
        [$solarHomeSystem, $manufacturer, $appliance] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'device_serial' => $device->device_serial,
        ]);

        $this->assertSerialNumberChangeIsRejected($solarHomeSystem, $device, $manufacturer, $appliance);
    }

    public function testItDeletesSolarHomeSystemWithDeviceAndGeoInformation(): void {
        $this->createTestData();
        [$solarHomeSystem] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        $geo = GeographicalInformationFactory::new()->for($device, 'owner')->create();

        $response = $this->actingAs($this->user)->deleteJson("/api/solar-home-systems/{$solarHomeSystem->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('solar_home_systems', ['id' => $solarHomeSystem->id], 'tenant');
        $this->assertDatabaseMissing('devices', ['id' => $device->id], 'tenant');
        $this->assertDatabaseMissing('geographical_informations', ['id' => $geo->id], 'tenant');
    }

    public function testItRejectsDeletingSolarHomeSystemWithActivity(): void {
        $this->createTestData();
        [$solarHomeSystem] = $this->seedSolarHomeSystem();
        $device = $this->attachDevice($solarHomeSystem);
        TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => 1,
            'message' => $device->device_serial,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/solar-home-systems/{$solarHomeSystem->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('solar_home_systems', ['id' => $solarHomeSystem->id], 'tenant');
        $this->assertDatabaseHas('devices', ['id' => $device->id], 'tenant');
    }

    public function testItReturns404WhenDeletingMissingSolarHomeSystem(): void {
        $this->createTestData();

        $response = $this->actingAs($this->user)->deleteJson('/api/solar-home-systems/999999');

        $response->assertStatus(404);
    }

    private function assertSerialNumberChangeIsRejected(
        SolarHomeSystem $solarHomeSystem,
        Device $device,
        Manufacturer $manufacturer,
        Appliance $appliance,
    ): void {
        $originalSerialNumber = $solarHomeSystem->serial_number;

        $response = $this->actingAs($this->user)->putJson(
            "/api/solar-home-systems/{$solarHomeSystem->id}",
            ['serial_number' => 'SHS-NEW-0001', 'manufacturer_id' => $manufacturer->id, 'appliance_id' => $appliance->id]
        );

        $response->assertStatus(422);
        $this->assertSame($originalSerialNumber, $solarHomeSystem->refresh()->serial_number);
        $this->assertSame($originalSerialNumber, $device->refresh()->device_serial);
    }

    private function attachDevice(SolarHomeSystem $solarHomeSystem): Device {
        return Device::factory()
            ->for($solarHomeSystem, 'device')
            ->createOne(['device_serial' => $solarHomeSystem->serial_number]);
    }

    /**
     * @return array{0: SolarHomeSystem, 1: Manufacturer, 2: Appliance}
     */
    private function seedSolarHomeSystem(): array {
        $originalManufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'serial_number' => fake()->unique()->numerify('SHS########'),
            'manufacturer_id' => $originalManufacturer->id,
            'appliance_id' => $this->createAppliance()->id,
        ]);

        $newManufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $newAppliance = $this->createAppliance();

        return [$solarHomeSystem, $newManufacturer, $newAppliance];
    }

    private function createAppliance(): Appliance {
        return ApplianceFactory::new()->create([
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
    }
}
