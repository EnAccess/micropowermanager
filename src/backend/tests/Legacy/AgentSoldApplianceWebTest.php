<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentSoldApplianceWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsSoldApplianceList(): void {
        $this->createTestData();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createPerson();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/sold/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), 1);
    }

    public function actingAs(Authenticatable $user, $driver = null): static {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
