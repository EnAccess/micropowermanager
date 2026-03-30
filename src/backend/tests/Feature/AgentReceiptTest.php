<?php

namespace Tests\Feature;

use App\Models\AgentBalanceHistory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentReceiptTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsReceipts(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $this->createAgentReceipt();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/receipt/%s', $agentId));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
        $this->assertEquals($agentId, $response['data'][0]['agent']['id']);
        $agentBalance = AgentBalanceHistory::query()->where('agent_id', $agentId)->sum('amount');
        $this->assertEquals($response['data'][0]['agent']['balance'], $agentBalance);
    }

    public function testUserGetsAllReceipts(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $this->createAgentReceipt();
        $response = $this->actingAs($this->user)->get('/api/agents/receipt');
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
    }

    public function testUserCreatesNewReceipt(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $postData = [
            'agent_id' => $agentId,
            'amount' => 50,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents/receipt', $postData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['agent_id'], $postData['agent_id']);
        $this->assertEquals($response['data']['amount'], $postData['amount']);
    }
}
