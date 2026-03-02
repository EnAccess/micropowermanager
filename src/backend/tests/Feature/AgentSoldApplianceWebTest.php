<?php

namespace Tests\Feature;

use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentSoldApplianceWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsSoldApplianceList(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/sold/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
    }
}
