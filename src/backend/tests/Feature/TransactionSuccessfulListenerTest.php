<?php

namespace Tests\Feature;

use App\Events\TransactionSuccessfulEvent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use Tests\CreateEnvironments;
use Tests\TestCase;

class TransactionSuccessfulListenerTest extends TestCase {
    use CreateEnvironments;

    public function testSuccessEventMarksDeferredPaymentAgentTransactionConfirmed(): void {
        $transaction = $this->createConfirmableAgentTransaction();

        event(new TransactionSuccessfulEvent($transaction));

        $this->assertSame(1, (int) $transaction->originalTransaction()->first()->status);
        $this->assertLedgerRowCounts($transaction, 1, 1);
    }

    public function testReplayedSuccessEventDoesNotCreditTheAgentTwice(): void {
        $transaction = $this->createConfirmableAgentTransaction();

        // A queue retry can replay the event for an already-credited transaction.
        event(new TransactionSuccessfulEvent($transaction));
        event(new TransactionSuccessfulEvent($transaction));

        $this->assertLedgerRowCounts($transaction, 1, 1);
    }

    private function createConfirmableAgentTransaction(): Transaction {
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

        return $agentTransaction->transaction()->create([
            'amount' => 500,
            'sender' => 'Agent-'.$this->agent->id,
            'message' => 'MTR-LISTENER-001',
            'type' => Transaction::TYPE_DEFERRED_PAYMENT,
            'original_transaction_type' => 'agent_transaction',
        ]);
    }

    private function assertLedgerRowCounts(Transaction $transaction, int $balanceRows, int $commissionRows): void {
        $rows = AgentBalanceHistory::query()
            ->where('transaction_id', $transaction->id)
            ->get()
            ->countBy('trigger_type');

        $this->assertSame($balanceRows, $rows[AgentTransaction::RELATION_NAME] ?? 0);
        $this->assertSame($commissionRows, $rows[AgentCommission::RELATION_NAME] ?? 0);
    }
}
