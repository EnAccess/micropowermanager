<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Device;
use App\Models\SolarHomeSystem;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use Database\Factories\ManufacturerFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class OpenPaygoDeviceConfigurationTest extends TestCase {
    use CreateEnvironments;

    public function testOnlySupportedOpenPaygoDevicesCanBeConfigured(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create(['api_name' => 'SparkShsApi']);
        $solarHomeSystem = SolarHomeSystem::query()->create([
            'serial_number' => 'CONFIGTEST01',
            'appliance_id' => 1,
            'manufacturer_id' => $manufacturer->id,
        ]);
        $device = Device::query()->create([
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_id' => $solarHomeSystem->id,
            'device_serial' => $solarHomeSystem->serial_number,
        ]);

        $this->actingAs($this->user)->postJson($this->configurationUrl($device), $this->payload())
            ->assertUnprocessable()->assertJsonPath('message', 'Select an OpenPAYGO solar home system or e-bike before configuring provisioning.');
        $manufacturer->api_name = 'OpenPaygoApi';
        $manufacturer->save();
        $device->device_type = 'meter';
        $device->save();
        $this->postJson($this->configurationUrl($device), $this->payload())->assertUnprocessable();
        $device->device_type = SolarHomeSystem::RELATION_NAME;
        $device->save();
        $solarHomeSystem->manufacturer_id = 999999999;
        $solarHomeSystem->save();
        $this->postJson($this->configurationUrl($device), $this->payload())->assertUnprocessable();
        $this->assertDatabaseMissing('openpaygo_device_configurations', ['device_id' => $device->id], 'tenant');
    }

    public function testConfigurationUpdatePreservesManagedCounterAndNeverReturnsSecret(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create(['api_name' => 'OpenPaygoApi']);
        $solarHomeSystem = SolarHomeSystem::query()->create([
            'serial_number' => 'CONFIGTEST02',
            'appliance_id' => 1,
            'manufacturer_id' => $manufacturer->id,
        ]);
        $device = Device::query()->create([
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_id' => $solarHomeSystem->id,
            'device_serial' => $solarHomeSystem->serial_number,
        ]);
        $payload = $this->payload();
        $payload['nextCounter'] = 17;
        $this->actingAs($this->user)->postJson($this->configurationUrl($device), $payload)->assertCreated();
        $payload['nextCounter'] = 0;
        $this->postJson($this->configurationUrl($device), $payload)->assertUnprocessable()
            ->assertJsonPath('message', 'The OpenPAYGO counter is managed by token issuance and cannot be changed through provisioning.');
        unset($payload['nextCounter']);
        $payload['timeDivider'] = 4;
        $response = $this->postJson($this->configurationUrl($device), $payload)->assertOk()
            ->assertJsonPath('data.configured', true);
        $this->assertStringNotContainsString($payload['secretKeyHex'], $response->getContent());
        $this->assertStringNotContainsString('secret_key_hex', $response->getContent());
        $configuration = resolve(OpenPaygoDeviceConfigurationService::class)->getForDevice($device);
        $this->assertSame(17, $configuration['nextCounter']);
        $this->assertSame(4, $configuration['timeDivider']);
    }

    private function configurationUrl(Device $device): string {
        return '/api/devices/'.$device->id.'/openpaygo-configuration';
    }

    private function payload(): array {
        return [
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 728,
            'nextCounter' => 0,
            'timeDivider' => 1,
        ];
    }
}
