<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\CreateEnvironments;
use Tests\TestCase;

class RealignAgentReceiptAmountTest extends TestCase {
    use CreateEnvironments;

    public function testReceiptAmountsAreRealignedWithWhatTheLedgerDeducted(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        // Written with the query builder so the receipt observer does not fire and
        // the rows land exactly as seeded.
        $settled = $this->insertReceipt(50.0, [5.0]);
        $withoutCommission = $this->insertReceipt(50.0, [0.0]);
        $split = $this->insertReceipt(50.0, [2.0, 3.0]);

        $this->runMigration();

        $amounts = DB::connection('tenant')->table('agent_receipts')
            ->whereIn('id', [$settled, $withoutCommission, $split])
            ->pluck('amount', 'id');

        $this->assertSame(55.0, (float) $amounts[$settled]);
        // nothing was credited at that visit, so the ledger deducted the amount alone
        $this->assertSame(50.0, (float) $amounts[$withoutCommission]);
        // several detail rows on one receipt are summed, not picked from
        $this->assertSame(55.0, (float) $amounts[$split]);
    }

    public function testDerivedDetailColumnsAreGone(): void {
        foreach (['collected', 'summary', 'earlier'] as $column) {
            $this->assertFalse(
                Schema::connection('tenant')->hasColumn('agent_receipt_details', $column),
                sprintf('agent_receipt_details.%s should have been dropped', $column)
            );
        }
    }

    /**
     * @param array<int, float> $commissionCredited one detail row per entry
     */
    private function insertReceipt(float $amount, array $commissionCredited): int {
        $receiptId = DB::connection('tenant')->table('agent_receipts')->insertGetId([
            'agent_id' => $this->agent->id,
            'user_id' => $this->user->id,
            'amount' => $amount,
            'last_controlled_balance_history_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($commissionCredited as $credited) {
            DB::connection('tenant')->table('agent_receipt_details')->insert([
                'agent_receipt_id' => $receiptId,
                'due' => $amount,
                'since_last_visit' => 0,
                'commission_credited' => $credited,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $receiptId;
    }

    private function runMigration(): void {
        $migration = require database_path(
            'migrations/tenant/2026_09_08_000000_realign_agent_receipt_amount_with_ledger.php'
        );
        $migration->up();
    }
}
