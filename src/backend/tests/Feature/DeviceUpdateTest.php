<?php

namespace Tests\Feature;

use App\Models\Meter\Meter;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceUpdateTest extends TestCase {
    use CreateEnvironments;

    public function testReassigningAnOwnerReturnsTheNewOwner(): void {
        $this->createTestData();
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => ManufacturerFactory::new()->create()->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'serial_number' => 'OWNER-CHANGE-1',
        ]);
        $device = DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
        ]);
        $newOwner = PersonFactory::new()->create();

        $response = $this->actingAs($this->user)->putJson("/api/devices/{$device->id}", [
            'id' => $device->id,
            'person_id' => $newOwner->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.person.id', $newOwner->id);
        $response->assertJsonPath('data.person.name', $newOwner->name);
        $response->assertJsonPath('data.person.surname', $newOwner->surname);
    }
}
