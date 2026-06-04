<?php

namespace Tests\Feature;

use App\Events\TransactionSuccessfulEvent;
use App\Models\AgentBalanceHistory;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use Tests\CreateEnvironments;
use Tests\TestCase;

class TransactionSuccessfulListenerTest extends TestCase {
    use CreateEnvironments;

    public function testSuccessEventMarksDeferredPaymentAgentTransactionConfirmed(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $agentTransaction = AgentTransaction::query()->create([
            'agent_id' => $this->agent->id,
            'mobile_device_id' => $this->agent->mobile_device_id,
            'status' => 0,
        ]);
        $transaction = $agentTransaction->transaction()->create([
            'amount' => 500,
            'sender' => 'Agent-'.$this->agent->id,
            'message' => 'MTR-LISTENER-001',
            'type' => Transaction::TYPE_DEFERRED_PAYMENT,
            'original_transaction_type' => 'agent_transaction',
        ]);

        event(new TransactionSuccessfulEvent($transaction));

        $this->assertSame(1, (int) $agentTransaction->fresh()->status);
        $this->assertTrue(
            AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->exists(),
            'sendResult() should record the agent balance history for a confirmed transaction'
        );
    }
}
