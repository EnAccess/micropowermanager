<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Lib\IManufacturerDeviceInfo;
use App\Models\Device;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\SolarHomeSystemFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class SolarHomeSystemDeviceInfoTest extends TestCase {
    use CreateEnvironments;

    public function testItReportsDeviceAsMapped(): void {
        $this->createTestData();
        $solarHomeSystem = $this->seedSolarHomeSystem('MappedApi');
        $this->bindManufacturerApi('MappedApi', [
            'mapped' => true,
            'device' => ['code' => '312', 'name' => 'SK-312 Pro EasyBuy', 'keypad_type' => 2],
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/solar-home-systems/{$solarHomeSystem->id}/device-info");

        $response->assertStatus(200);
        $response->assertJsonPath('data.supported', true);
        $response->assertJsonPath('data.mapped', true);
        $response->assertJsonPath('data.device.name', 'SK-312 Pro EasyBuy');
    }

    public function testItReportsDeviceAsNotMapped(): void {
        $this->createTestData();
        $solarHomeSystem = $this->seedSolarHomeSystem('NotMappedApi');
        $this->bindManufacturerApi('NotMappedApi', ['mapped' => false, 'device' => null]);

        $response = $this->actingAs($this->user)->getJson("/api/solar-home-systems/{$solarHomeSystem->id}/device-info");

        $response->assertStatus(200);
        $response->assertJsonPath('data.supported', true);
        $response->assertJsonPath('data.mapped', false);
        $response->assertJsonPath('data.device', null);
    }

    public function testItReportsUnsupportedWhenManufacturerHasNoApi(): void {
        $this->createTestData();
        $solarHomeSystem = $this->seedSolarHomeSystem(null);

        $response = $this->actingAs($this->user)->getJson("/api/solar-home-systems/{$solarHomeSystem->id}/device-info");

        $response->assertStatus(200);
        $response->assertJsonPath('data.supported', false);
    }

    public function testItReturns404WhenSolarHomeSystemMissing(): void {
        $this->createTestData();

        $response = $this->actingAs($this->user)->getJson('/api/solar-home-systems/999999/device-info');

        $response->assertStatus(404);
    }

    private function seedSolarHomeSystem(?string $apiName): SolarHomeSystem {
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
        DeviceFactory::new()->create([
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_serial' => '996995411',
        ]);

        return $solarHomeSystem;
    }

    /**
     * @param array{mapped: bool, device: array<string, mixed>|null} $info
     */
    private function bindManufacturerApi(string $apiName, array $info): void {
        $this->app->bind($apiName, fn () => new class($info) implements IManufacturerDeviceInfo {
            /** @param array{mapped: bool, device: array<string, mixed>|null} $info */
            public function __construct(private array $info) {}

            public function getDeviceInfo(Device $device): array {
                return $this->info;
            }
        });
    }
}
