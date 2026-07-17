<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\AgentReceiptDetail;
use App\Services\AgentBalanceHistoryService;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentReceiptTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsReceipts(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $this->createAgentReceipt();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/receipt/%s', $agentId));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
        $this->assertEquals($agentId, $response['data'][0]['agent']['id']);

        $balanceLedgerSum = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->whereIn('trigger_type', AgentBalanceHistoryService::BALANCE_TRIGGER_TYPES)
            ->sum('amount');
        $commissionLedgerSum = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentCommission::RELATION_NAME)
            ->sum('amount');
        $this->assertEquals($response['data'][0]['agent']['balance'], $balanceLedgerSum);
        $this->assertEquals($response['data'][0]['agent']['commission_revenue'], $commissionLedgerSum);
    }

    public function testUserGetsAllReceipts(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $this->createAgentReceipt();
        $response = $this->actingAs($this->user)->get('/api/agents/receipt');
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
    }

    public function testUserCreatesNewReceipt(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        // sale of 100 -> balance -100, due 100, accrued commission 100 * 0.05 = 5
        $this->createAgentTransaction(1, 100, $agentId);
        $postData = [
            'agent_id' => $agentId,
            'amount' => 50,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents/receipt', $postData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['agent_id'], $postData['agent_id']);
        $this->assertEquals($response['data']['amount'], $postData['amount']);

        // cash (50) plus pending commission (5) is credited to the balance ledger
        $receiptRow = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentReceipt::RELATION_NAME)
            ->sole();
        $this->assertEquals(55, $receiptRow->amount);

        // the commission payout is an explicit negative row on the commission ledger
        $payoutRow = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentCommission::RELATION_NAME)
            ->where('amount', '<', 0)
            ->sole();
        $this->assertEquals(-5, $payoutRow->amount);

        $agent = Agent::query()->find($agentId);
        $this->assertEquals(-45, $agent->balance);
        $this->assertEquals(0, $agent->commission_revenue);
        $this->assertEquals(45, $agent->due_to_energy_supplier);

        $detail = AgentReceiptDetail::query()->where('agent_receipt_id', $response['data']['id'])->sole();
        $this->assertEquals(5, $detail->commission_credited);
        $this->assertEquals(100, $detail->due);

        // each ledger sums to its agent field
        $this->assertEquals($agent->balance, AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->whereIn('trigger_type', AgentBalanceHistoryService::BALANCE_TRIGGER_TYPES)->sum('amount'));
        $this->assertEquals($agent->commission_revenue, AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentCommission::RELATION_NAME)->sum('amount'));
    }

    public function testReceiptWithoutPendingCommissionCreatesNoPayoutRow(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);

        // first receipt pays out the accrued commission (5)
        $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 40,
        ])->assertStatus(201);

        $historyCountBefore = AgentBalanceHistory::query()->where('agent_id', $agentId)->count();

        // second receipt has no pending commission -> exactly one new row
        $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 30,
        ])->assertStatus(201);

        $this->assertEquals(
            $historyCountBefore + 1,
            AgentBalanceHistory::query()->where('agent_id', $agentId)->count()
        );
        $agent = Agent::query()->find($agentId);
        $this->assertEquals(0, $agent->commission_revenue);
        $this->assertEquals(25, $agent->due_to_energy_supplier);
    }

    public function testReceiptExceedingAgentDueIsRejected(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);

        $response = $this->actingAs($this->user)->postJson('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 200,
        ]);
        $response->assertStatus(422);
    }

    public function testReceiptForAgentWithoutActivityIsRejected(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;

        $response = $this->actingAs($this->user)->postJson('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 50,
        ]);
        $response->assertStatus(422);
    }

    private function createReceiptEnvironment(): void {
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
    }
}
