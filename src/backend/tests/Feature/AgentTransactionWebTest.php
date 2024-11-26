<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentTransactionWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsTransactions() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agentTransactionCount = 1;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/transactions/%s', $agentId));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), 1);
        $this->assertEquals($response['data'][0]['amount'], $amount);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
