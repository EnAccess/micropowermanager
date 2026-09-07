<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\AgentReceiptDetail;
use App\Services\AgentBalanceHistoryService;
use Database\Factories\AgentBalanceHistoryFactory;
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

    public function testFullSettlementClearsTheBalanceAndCreditsTheCommission(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        // sale of 100 -> balance 100 (company money held), accrued commission 100 * 0.05 = 5
        $this->createAgentTransaction(1, 100, $agentId);

        $response = $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 100,
        ]);
        $response->assertStatus(201);

        // the balance ledger is reduced by the amount collected alone
        $receiptRow = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentReceipt::RELATION_NAME)
            ->sole();
        $this->assertEquals(-100, $receiptRow->amount);

        // the commission the agent kept out of that cash leaves the commission ledger
        $payoutRow = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentCommission::RELATION_NAME)
            ->where('amount', '<', 0)
            ->sole();
        $this->assertEquals(-5, $payoutRow->amount);

        $agent = Agent::query()->find($agentId);
        $this->assertEquals(0, $agent->balance);
        $this->assertEquals(0, $agent->commission_revenue);

        $detail = AgentReceiptDetail::query()->where('agent_receipt_id', $response['data']['id'])->sole();
        $this->assertEquals(100, $detail->due);
        $this->assertEquals(5, $detail->commission_credited);

        $this->assertLedgersMatchAgent($agentId);
    }

    public function testPartialSettlementLeavesTheCommissionPending(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $historyCountBefore = AgentBalanceHistory::query()->where('agent_id', $agentId)->count();

        $response = $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 40,
        ]);
        $response->assertStatus(201);

        // one ledger row only: nothing was credited, so there is no payout to record
        $this->assertEquals(
            $historyCountBefore + 1,
            AgentBalanceHistory::query()->where('agent_id', $agentId)->count()
        );

        $agent = Agent::query()->find($agentId);
        $this->assertEquals(60, $agent->balance);
        $this->assertEquals(5, $agent->commission_revenue);

        $detail = AgentReceiptDetail::query()->where('agent_receipt_id', $response['data']['id'])->sole();
        $this->assertEquals(0, $detail->commission_credited);

        $this->assertLedgersMatchAgent($agentId);
    }

    public function testCommissionCreditedIsCappedAtTheAmountCollected(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);

        // appliance commission accrues on the full appliance cost while only the down
        // payment reaches the balance, so pending commission can exceed the money held
        AgentBalanceHistoryFactory::new()->create([
            'agent_id' => $agentId,
            'amount' => 200,
            'available_balance' => 0,
            'trigger_id' => $this->agentCommission->id,
            'trigger_type' => AgentCommission::RELATION_NAME,
        ]);
        $this->assertEquals(205, Agent::query()->find($agentId)->commission_revenue);

        $response = $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 100,
        ]);
        $response->assertStatus(201);

        $agent = Agent::query()->find($agentId);
        $this->assertEquals(0, $agent->balance);
        $this->assertEquals(105, $agent->commission_revenue);

        $detail = AgentReceiptDetail::query()->where('agent_receipt_id', $response['data']['id'])->sole();
        $this->assertEquals(100, $detail->commission_credited);
        // the cash the operator counts is never negative
        $receipt = AgentReceipt::query()->find($response['data']['id']);
        $this->assertEquals(0, $receipt->amount - $detail->commission_credited);

        $this->assertLedgersMatchAgent($agentId);
    }

    public function testFirstReceiptReportsEverythingCollectedSinceTheAgentStarted(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);

        $response = $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 40,
        ]);
        $response->assertStatus(201);

        $detail = AgentReceiptDetail::query()->where('agent_receipt_id', $response['data']['id'])->sole();
        $this->assertEquals(100, $detail->since_last_visit);
    }

    public function testSecondReceiptReportsOnlySalesAfterThePreviousVisit(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);
        $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 100,
        ])->assertStatus(201);

        $this->createAgentTransaction(1, 60, $agentId);
        $response = $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 60,
        ]);
        $response->assertStatus(201);

        $detail = AgentReceiptDetail::query()->where('agent_receipt_id', $response['data']['id'])->sole();
        $this->assertEquals(60, $detail->since_last_visit);
        $this->assertEquals(60, $detail->due);

        $agent = Agent::query()->find($agentId);
        $this->assertEquals(0, $agent->balance);
        $this->assertLedgersMatchAgent($agentId);
    }

    public function testReceiptRowSnapshotsThePostReceiptBalance(): void {
        $this->createReceiptEnvironment();
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction(1, 100, $agentId);

        $this->actingAs($this->user)->post('/api/agents/receipt', [
            'agent_id' => $agentId,
            'amount' => 70,
        ])->assertStatus(201);

        $receiptRow = AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentReceipt::RELATION_NAME)
            ->sole();
        $this->assertEquals(Agent::query()->find($agentId)->balance, $receiptRow->available_balance);
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

    private function assertLedgersMatchAgent(int $agentId): void {
        $agent = Agent::query()->find($agentId);
        $this->assertEquals($agent->balance, AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->whereIn('trigger_type', AgentBalanceHistoryService::BALANCE_TRIGGER_TYPES)->sum('amount'));
        $this->assertEquals($agent->commission_revenue, AgentBalanceHistory::query()->where('agent_id', $agentId)
            ->where('trigger_type', AgentCommission::RELATION_NAME)->sum('amount'));
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
