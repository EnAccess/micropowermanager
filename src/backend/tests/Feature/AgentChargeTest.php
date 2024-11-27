<?php

namespace Tests\Feature;

use App\Models\Agent;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentChargeTest extends TestCase {
    use CreateEnvironments;

    public function testUserCreatesNewBalanceForAgent() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agentBalance = $this->agents[0]->balance;
        $postData = [
            'agent_id' => $this->agents[0]->id,
            'amount' => 50,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents/charge', $postData);
        $response->assertStatus(201);
        $agent = Agent::query()->find($this->agents[0]->id);
        $this->assertEquals($agent->balance, $agentBalance + $postData['amount']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
