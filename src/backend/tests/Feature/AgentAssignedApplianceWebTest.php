<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentAssignedApplianceWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsAssignedApplianceList() {
        $this->createTestData();
        $agentCount = 1;
        $this->createAgent($agentCount);
        $applianceCount = 5;
        $this->createAssignedAppliances($applianceCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/assigned/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $applianceCount);
    }

    public function testUserAssignsAnAssignedApplianceToAgent() {
        $this->createTestData();
        $agentCount = 1;
        $this->createAgent($agentCount);
        $applianceCount = 1;
        $this->createAssetType($applianceCount);
        $postData = [
            'agent_id' => $this->agents[0]->id,
            'user_id' => $this->user->id,
            'appliance_type_id' => $this->assetTypes[0]->id,
            'cost' => $this->faker->randomFloat(2, 1, 100),
        ];

        $response = $this->actingAs($this->user)->post('/api/agents/assigned', $postData);
        $response->assertStatus(201);

        $this->assertEquals($response['data']['agent_id'], $this->agents[0]->id);
        $this->assertEquals($response['data']['cost'], $postData['cost']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
