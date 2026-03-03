<?php

namespace Tests\Feature;

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
}
