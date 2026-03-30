<?php

namespace Tests\Feature;

use App\Models\Agent;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentChargeTest extends TestCase {
    use CreateEnvironments;

    public function testUserCreatesNewBalanceForAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
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
}
