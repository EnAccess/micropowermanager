<?php

namespace Tests\Feature;

use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentTransactionWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsTransactions(): void {
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
        $agentTransactionCount = 1;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/transactions/%s', $agentId));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
        $this->assertEquals($amount, $response['data'][0]['amount']);
    }
}
