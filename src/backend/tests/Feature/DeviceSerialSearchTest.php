<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceFactory;
use Database\Factories\ManufacturerFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceSerialSearchTest extends TestCase {
    use CreateEnvironments;

    public function testDeviceIndexFiltersUnassignedDevicesBySerial(): void {
        $this->createTestData();
        $this->createApplianceType();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $this->applianceType->id,
        ]);

        $match = $this->createShsDevice('6551180400', $appliance->id);
        $otherSerial = $this->createShsDevice('6550986800', $appliance->id);

        $response = $this->actingAs($this->user)
            ->get(sprintf('/api/devices?unassigned=1&appliance_id=%d&serial=11804', $appliance->id));

        $response->assertStatus(200);
        $serials = collect($response->json('data'))->pluck('device_serial')->all();
        $this->assertContains($match->device_serial, $serials);
        $this->assertNotContains($otherSerial->device_serial, $serials);
    }

    public function testSerialFilterFindsDevicesBeyondTheFirstPage(): void {
        $this->createTestData();
        $this->createApplianceType();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $this->applianceType->id,
        ]);

        // The serial under test is created first, so it is the oldest record and
        // would fall outside the default first page once newer devices exist.
        $target = $this->createShsDevice('6551211200', $appliance->id);
        foreach (range(1, 60) as $index) {
            $this->createShsDevice(sprintf('NEWER-%04d', $index), $appliance->id);
        }

        $response = $this->actingAs($this->user)
            ->get(sprintf('/api/devices?unassigned=1&appliance_id=%d&per_page=50&serial=6551211200', $appliance->id));

        $response->assertStatus(200);
        $serials = collect($response->json('data'))->pluck('device_serial')->all();
        $this->assertContains($target->device_serial, $serials);
    }

    private function createShsDevice(string $serial, int $applianceId): Device {
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $shs = SolarHomeSystem::query()->create([
            'serial_number' => $serial,
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $applianceId,
        ]);

        return Device::query()->create([
            'person_id' => null,
            'device_id' => $shs->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_serial' => $serial,
        ]);
    }
}
