<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops agent ledger rows duplicated against a single transaction and rebuilds
 * the snapshots and agent aggregates derived from them.
 */
return new class extends Migration {
    private const DUPLICATED_TRIGGERS = ['agent_transaction', 'agent_commission'];
    private const BALANCE_TRIGGERS = ['agent_transaction', 'agent_appliance', 'agent_receipt', 'agent_charge'];
    private const COMMISSION_TRIGGER = 'agent_commission';

    public function up(): void {
        $connection = DB::connection('tenant');

        $connection->transaction(function () use ($connection) {
            $this->deleteDuplicates($connection);
            $this->recomputeSnapshots($connection);
        });
    }

    // Irreversible: recreating the deleted rows would re-inflate every agent.
    public function down(): void {}

    /**
     * Keeps the earliest row of each identical group. Charge, receipt and
     * appliance rows come from observers outside the event path, so genuine
     * repeats of those must survive.
     */
    private function deleteDuplicates(Connection $connection): void {
        $connection->table('agent_balance_histories as duplicate')
            ->join('agent_balance_histories as original', function ($join) {
                $join->on('original.agent_id', '=', 'duplicate.agent_id')
                    ->on('original.transaction_id', '=', 'duplicate.transaction_id')
                    ->on('original.trigger_type', '=', 'duplicate.trigger_type')
                    ->on('original.trigger_id', '=', 'duplicate.trigger_id')
                    ->on('original.amount', '=', 'duplicate.amount')
                    ->on('original.id', '<', 'duplicate.id');
            })
            ->whereNotNull('duplicate.transaction_id')
            ->whereIn('duplicate.trigger_type', self::DUPLICATED_TRIGGERS)
            ->delete();
    }

    /**
     * Replays each agent's ledger to rebuild the snapshots and aggregates,
     * mirroring AgentBalanceHistoryObserver::created. Query builder throughout so
     * no observer fires while the data migration runs.
     */
    private function recomputeSnapshots(Connection $connection): void {
        $agentIds = $connection->table('agent_balance_histories')->distinct()->pluck('agent_id');

        foreach ($agentIds as $agentId) {
            $rows = $connection->table('agent_balance_histories')
                ->where('agent_id', $agentId)
                ->orderBy('id')
                ->get(['id', 'trigger_type', 'amount']);

            $runningBalance = 0.0;
            $commissionRevenue = 0.0;

            foreach ($rows as $row) {
                if ($row->trigger_type === self::COMMISSION_TRIGGER) {
                    $commissionRevenue += (float) $row->amount;
                } elseif (in_array($row->trigger_type, self::BALANCE_TRIGGERS, true)) {
                    $runningBalance += (float) $row->amount;
                }

                $connection->table('agent_balance_histories')
                    ->where('id', $row->id)
                    ->update(['available_balance' => $runningBalance]);
            }

            $connection->table('agents')->where('id', $agentId)->update([
                'balance' => $runningBalance,
                'commission_revenue' => $commissionRevenue,
            ]);
        }
    }
};
