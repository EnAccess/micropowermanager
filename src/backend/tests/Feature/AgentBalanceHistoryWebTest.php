<?php

namespace Tests\Feature;

use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentBalanceHistoryWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsBalanceHistories(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $agentTransactionCount = 1;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, 100, $agentId);
        $agentBalanceHistoryCount = count(['agent_transaction', 'agent_commission']);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/balance/history/%s', $agentId));
        $response->assertStatus(200);
        $this->assertEquals($agentBalanceHistoryCount, count($response['data']));
    }
}
