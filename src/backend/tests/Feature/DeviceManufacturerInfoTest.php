<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ManufacturerMappingStatus;
use App\Jobs\VerifyDeviceMappingJob;
use App\Lib\IManufacturerDeviceControl;
use App\Models\Device;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\SolarHomeSystemFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceManufacturerInfoTest extends TestCase {
    use CreateEnvironments;

    public function testItReportsDeviceAsMapped(): void {
        $this->createTestData();
        $device = $this->seedDevice('MappedApi');
        $this->bindManufacturerApi('MappedApi', [
            'mapped' => true,
            'device' => ['code' => '312', 'name' => 'SK-312 Pro EasyBuy'],
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info");

        $response->assertStatus(200);
        $response->assertJsonPath('data.supported', true);
        $response->assertJsonPath('data.mapped', true);
        $response->assertJsonPath('data.device.name', 'SK-312 Pro EasyBuy');
    }

    public function testItReportsDeviceAsNotMapped(): void {
        $this->createTestData();
        $device = $this->seedDevice('NotMappedApi');
        $this->bindManufacturerApi('NotMappedApi', ['mapped' => false, 'device' => null]);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info");

        $response->assertStatus(200);
        $response->assertJsonPath('data.supported', true);
        $response->assertJsonPath('data.mapped', false);
        $response->assertJsonPath('data.device', null);
    }

    public function testItReportsUnsupportedWhenManufacturerHasNoApi(): void {
        $this->createTestData();
        $device = $this->seedDevice(null);

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info");

        $response->assertStatus(200);
        $response->assertJsonPath('data.supported', false);
    }

    public function testItReturns404WhenDeviceMissing(): void {
        $this->createTestData();

        $response = $this->actingAs($this->user)->getJson('/api/devices/999999/device-info');

        $response->assertStatus(404);
    }

    public function testItRequiresAuthentication(): void {
        $this->createTestData();
        $device = $this->seedDevice('MappedApi');

        $response = $this->getJson("/api/devices/{$device->id}/device-info");

        $response->assertUnauthorized();
    }

    public function testItPersistsStatusWhenChecked(): void {
        $this->createTestData();
        $device = $this->seedDevice('MappedApi');
        $this->bindManufacturerApi('MappedApi', ['mapped' => true, 'device' => ['code' => '312']]);

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info")->assertStatus(200);

        $device->refresh();
        $this->assertSame(ManufacturerMappingStatus::Mapped, $device->manufacturer_mapping_status);
        $this->assertNotNull($device->manufacturer_mapping_checked_at);
    }

    public function testItFiltersDevicesByMappingStatus(): void {
        $this->createTestData();
        $this->seedDevice(null, '111')->update(['manufacturer_mapping_status' => ManufacturerMappingStatus::Mapped]);
        $this->seedDevice(null, '222')->update(['manufacturer_mapping_status' => ManufacturerMappingStatus::NotMapped]);

        $response = $this->actingAs($this->user)->getJson('/api/devices?manufacturer_mapping_status=not_mapped');

        $response->assertStatus(200);
        $response->assertJsonFragment(['device_serial' => '222']);
        $response->assertJsonMissing(['device_serial' => '111']);
    }

    public function testTheVerifyJobPersistsStatus(): void {
        $this->createTestData();
        $device = $this->seedDevice('JobApi', '333');
        $this->bindManufacturerApi('JobApi', ['mapped' => false, 'device' => null]);

        new VerifyDeviceMappingJob($this->companyId, $device->id)->executeJob();

        $device->refresh();
        $this->assertSame(ManufacturerMappingStatus::NotMapped, $device->manufacturer_mapping_status);
        $this->assertNotNull($device->manufacturer_mapping_checked_at);
    }

    private function seedDevice(?string $apiName, string $serial = '996995411'): Device {
        $manufacturer = ManufacturerFactory::new()->create([
            'type' => 'shs',
            'api_name' => $apiName,
        ]);
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
        ]);

        return DeviceFactory::new()->create([
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_serial' => $serial,
        ]);
    }

    /**
     * @param array{mapped: bool, device: array<string, mixed>|null} $info
     */
    private function bindManufacturerApi(string $apiName, array $info): void {
        $this->app->bind($apiName, fn () => new class($info) implements IManufacturerDeviceControl {
            /** @param array{mapped: bool, device: array<string, mixed>|null} $info */
            public function __construct(private array $info) {}

            public function getDeviceInfo(Device $device): array {
                return $this->info;
            }
        });
    }
}
