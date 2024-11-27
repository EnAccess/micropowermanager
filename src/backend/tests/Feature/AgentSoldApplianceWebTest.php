<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentSoldApplianceWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsSoldApplianceList() {
        $this->createTestData();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createPerson();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/sold/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), 1);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
