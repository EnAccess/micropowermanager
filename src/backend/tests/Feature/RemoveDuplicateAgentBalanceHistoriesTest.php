<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\CreateEnvironments;
use Tests\TestCase;

class RemoveDuplicateAgentBalanceHistoriesTest extends TestCase {
    use CreateEnvironments;

    public function testDuplicateTransactionRowsAreRemovedAndTheLedgerIsRebuilt(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $agentId = $this->agent->id;
        $commissionId = $this->agentCommission->id;

        // Written with the query builder so the balance observer does not fire and
        // the rows land exactly as seeded.
        $this->insertLedgerRows([
            ['trigger_type' => 'agent_transaction', 'trigger_id' => 1, 'transaction_id' => 1, 'amount' => 100.0],
            ['trigger_type' => 'agent_commission', 'trigger_id' => $commissionId, 'transaction_id' => 1, 'amount' => 5.0],
            ['trigger_type' => 'agent_transaction', 'trigger_id' => 1, 'transaction_id' => 1, 'amount' => 100.0],
            ['trigger_type' => 'agent_commission', 'trigger_id' => $commissionId, 'transaction_id' => 1, 'amount' => 5.0],
            // Charges carry no transaction id and are written outside the event
            // path, so two identical ones are legitimate and must both survive.
            ['trigger_type' => 'agent_charge', 'trigger_id' => 1, 'transaction_id' => null, 'amount' => 50.0],
            ['trigger_type' => 'agent_charge', 'trigger_id' => 1, 'transaction_id' => null, 'amount' => 50.0],
        ]);

        DB::connection('tenant')->table('agents')->where('id', $agentId)->update([
            'balance' => 300.0,
            'commission_revenue' => 10.0,
        ]);

        $this->runMigration();

        $rows = DB::connection('tenant')->table('agent_balance_histories')
            ->where('agent_id', $agentId)
            ->orderBy('id')
            ->get(['trigger_type', 'amount', 'available_balance']);

        $this->assertSame(
            ['agent_transaction', 'agent_commission', 'agent_charge', 'agent_charge'],
            $rows->pluck('trigger_type')->all()
        );
        // The commission row snapshots the balance without moving it.
        $this->assertSame([100.0, 100.0, 150.0, 200.0], $rows->pluck('available_balance')->map(floatval(...))->all());

        $agent = DB::connection('tenant')->table('agents')->find($agentId);
        $this->assertSame(200.0, (float) $agent->balance);
        $this->assertSame(5.0, (float) $agent->commission_revenue);
    }

    /**
     * @param array<int, array{trigger_type: string, trigger_id: int, transaction_id: int|null, amount: float}> $rows
     */
    private function insertLedgerRows(array $rows): void {
        foreach ($rows as $row) {
            DB::connection('tenant')->table('agent_balance_histories')->insert($row + [
                'agent_id' => $this->agent->id,
                'available_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function runMigration(): void {
        $migration = require database_path(
            'migrations/tenant/2026_07_30_000000_remove_duplicate_agent_balance_histories.php'
        );
        $migration->up();
    }
}
