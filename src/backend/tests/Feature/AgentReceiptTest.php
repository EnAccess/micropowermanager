<?php

namespace Tests\Feature;

use App\Models\AgentBalanceHistory;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentReceiptTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsReceipts() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agentTransactionCount = 1;
        $this->createAgentTransaction($agentTransactionCount);
        $this->createAgentReceipt();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/receipt/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), 1);
        $this->assertEquals($response['data'][0]['agent']['id'], $this->agents[0]->id);
        $agentBalance = AgentBalanceHistory::query()->where('agent_id', $this->agents[0]->id)->sum('amount');
        $this->assertEquals($response['data'][0]['agent']['balance'], $agentBalance);
    }

    public function testUserGetsAllReceipts() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAgentTransaction();
        $this->createAgentReceipt();
        $response = $this->actingAs($this->user)->get('/api/agents/receipt');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), 1);
    }

    public function testUserCreatesNewReceipt() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAgentTransaction();
        $postData = [
            'agent_id' => $this->agents[0]->id,
            'amount' => 50,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents/receipt', $postData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['agent_id'], $postData['agent_id']);
        $this->assertEquals($response['data']['amount'], $postData['amount']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
