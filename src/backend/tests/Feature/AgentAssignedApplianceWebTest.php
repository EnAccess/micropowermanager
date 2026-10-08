<?php

namespace Tests\Feature;

use App\Models\AgentAssignedAppliances;
use Database\Factories\ApplianceFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentAssignedApplianceWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsAssignedApplianceList(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $agentCount = 1;
        $this->createAgentCommission();
        $this->createAgent($agentCount);
        $applianceCount = 5;
        $this->createAssignedAppliances($applianceCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/assigned/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $applianceCount);
    }

    public function testUserAssignsAnAssignedApplianceToAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $agentCount = 1;
        $this->createAgentCommission();
        $this->createAgent($agentCount);
        $this->createApplianceType();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $this->applianceTypes[0]->id,
        ]);
        $postData = [
            'agent_id' => $this->agents[0]->id,
            'user_id' => $this->user->id,
            'appliance_id' => $appliance->id,
            'cost' => $this->faker->randomFloat(2, 1, 100),
        ];

        $response = $this->actingAs($this->user)->post('/api/agents/assigned', $postData);
        $response->assertStatus(201);

        $this->assertEquals($response['data']['agent_id'], $this->agents[0]->id);
        $this->assertEquals($response['data']['cost'], $postData['cost']);
    }

    public function testUserRemovesAssignedApplianceFromAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances(2);
        $assignedAppliance = $this->assignedAppliances[0];

        $response = $this->actingAs($this->user)->delete(sprintf('/api/agents/assigned/%s', $assignedAppliance->id));
        $response->assertStatus(200);

        $this->assertSoftDeleted($assignedAppliance);
        $listResponse = $this->actingAs($this->user)->get(sprintf('/api/agents/assigned/%s', $this->agents[0]->id));
        $this->assertEquals(1, count($listResponse['data']));
    }

    public function testRemovedAssignedApplianceKeepsSoldApplianceHistory(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $assignedAppliance = $this->assignedAppliances[0];

        $response = $this->actingAs($this->user)->delete(sprintf('/api/agents/assigned/%s', $assignedAppliance->id));
        $response->assertStatus(200);

        $soldAppliance = $this->soldAppliances[0]->fresh();
        $this->assertNotNull($soldAppliance);
        $this->assertNotNull($soldAppliance->assignedAppliance);
        $this->assertEquals($this->agents[0]->id, $soldAppliance->assignedAppliance->agent?->id);
    }

    public function testRemovedAssignedApplianceCannotBeSold(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $assignedAppliance = $this->assignedAppliance;

        $this->actingAs($this->user)->delete(sprintf('/api/agents/assigned/%s', $assignedAppliance->id))->assertStatus(200);

        $response = $this->actingAs($this->user)->postJson('/api/agents/sold', [
            'agent_id' => $assignedAppliance->agent_id,
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'down_payment' => 0,
            'tenure' => 10,
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['agent_assigned_appliance_id']);
    }

    public function testRemovingUnknownAssignedApplianceReturnsNotFound(): void {
        $this->createTestData();

        $response = $this->actingAs($this->user)->delete('/api/agents/assigned/999999');
        $response->assertStatus(404);
        $this->assertEquals(0, AgentAssignedAppliances::withTrashed()->count());
    }
}
