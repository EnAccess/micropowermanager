<?php

namespace Tests\Feature;

use App\Models\AgentAssignedAppliances;
use App\Models\Device;
use App\Models\SolarHomeSystem;
use Database\Factories\ApplianceFactory;
use Database\Factories\ManufacturerFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentUnassignedDevicesTest extends TestCase {
    use CreateEnvironments;

    public function testAgentListsOnlyUnassignedDevicesForTheirAssignedAppliance(): void {
        $this->seedAgentAndAssignedAppliance();
        $applianceId = $this->assignedAppliance->appliance_id;

        $unassigned = $this->createShsDevice('SHS-FREE-0001', $applianceId, null);
        $assignedToPerson = $this->createShsDevice('SHS-SOLD-0001', $applianceId, $this->people[0]->id);

        $response = $this->actingAs($this->agent)
            ->getJson(sprintf('/api/app/agents/devices/unassigned?appliance_id=%d&type=solar_home_system', $applianceId));

        $response->assertStatus(200);
        $serials = collect($response->json('data'))->pluck('device_serial')->all();
        $this->assertContains($unassigned->device_serial, $serials);
        $this->assertNotContains($assignedToPerson->device_serial, $serials);
    }

    public function testListExcludesUnitsOfOtherAppliances(): void {
        $this->seedAgentAndAssignedAppliance();
        $applianceId = $this->assignedAppliance->appliance_id;

        $siblingAppliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $this->applianceType->id,
        ]);
        $siblingDevice = $this->createShsDevice('SHS-SIBLING-0001', $siblingAppliance->id, null);

        $response = $this->actingAs($this->agent)
            ->getJson(sprintf('/api/app/agents/devices/unassigned?appliance_id=%d&type=solar_home_system', $applianceId));

        $response->assertStatus(200);
        $serials = collect($response->json('data'))->pluck('device_serial')->all();
        $this->assertNotContains($siblingDevice->device_serial, $serials);
    }

    public function testAgentCannotListDevicesForApplianceNotAssignedToThem(): void {
        $this->seedAgentAndAssignedAppliance();

        $foreignAppliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $this->applianceType->id,
        ]);
        $this->createShsDevice('SHS-FOREIGN-0001', $foreignAppliance->id, null);

        $response = $this->actingAs($this->agent)
            ->getJson(sprintf(
                '/api/app/agents/devices/unassigned?appliance_id=%d&type=solar_home_system',
                $foreignAppliance->id
            ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appliance_id']);
    }

    public function testApplianceIdAndTypeAreRequired(): void {
        $this->seedAgentAndAssignedAppliance();

        $response = $this->actingAs($this->agent)
            ->getJson('/api/app/agents/devices/unassigned');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appliance_id', 'type']);
    }

    public function testTypeMustBeSupported(): void {
        $this->seedAgentAndAssignedAppliance();

        $response = $this->actingAs($this->agent)
            ->getJson(sprintf(
                '/api/app/agents/devices/unassigned?appliance_id=%d&type=bogus',
                $this->assignedAppliance->appliance_id
            ));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function testUnauthenticatedRequestIsRejected(): void {
        $response = $this->getJson('/api/app/agents/devices/unassigned?appliance_id=1&type=solar_home_system');

        $response->assertStatus(401);
    }

    private function seedAgentAndAssignedAppliance(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson(1, 1);
        $this->createApplianceType();

        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $this->applianceType->id,
        ]);
        $this->assignedAppliance = AgentAssignedAppliances::query()->create([
            'agent_id' => $this->agent->id,
            'user_id' => $this->user->id,
            'appliance_id' => $appliance->id,
            'cost' => 100,
        ]);
    }

    private function createShsDevice(string $serial, int $applianceId, ?int $personId): Device {
        $manufacturer = ManufacturerFactory::new()->isShsManufacturer()->create();
        $shs = SolarHomeSystem::query()->create([
            'serial_number' => $serial,
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $applianceId,
        ]);

        return Device::query()->create([
            'person_id' => $personId,
            'device_id' => $shs->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_serial' => $serial,
        ]);
    }
}
