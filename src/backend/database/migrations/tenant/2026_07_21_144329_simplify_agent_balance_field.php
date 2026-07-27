<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flips the agent balance model so a single signed `balance` means
 * "company money the agent currently holds": sales add, transfers (receipts)
 * subtract, and a charge (company funding the agent) adds.
 *
 * Mechanically this negates every balance-ledger history row EXCEPT charges
 * (a charge is already stored with the sign the new model wants) and the
 * commission ledger (whose meaning is unchanged). The agent aggregate and the
 * per-row snapshots are then recomputed from the transformed rows.
 *
 * The redundant `due_to_energy_supplier` / `due_to_supplier` columns are left
 * in place here and dropped by a follow-up migration, so this step stays
 * reversible on its own.
 */
return new class extends Migration {
    private const FLIPPED_TRIGGERS = ['agent_transaction', 'agent_appliance', 'agent_receipt'];
    private const BALANCE_TRIGGERS = ['agent_transaction', 'agent_appliance', 'agent_receipt', 'agent_charge'];

    public function up(): void {
        $connection = DB::connection('tenant');

        $connection->table('agent_balance_histories')
            ->whereIn('trigger_type', self::FLIPPED_TRIGGERS)
            ->update(['amount' => $connection->raw('amount * -1')]);

        // Risk balance was a negative floor ("balance may not fall below"); it is
        // now a positive ceiling ("balance may not rise above").
        $connection->table('agent_commissions')
            ->update(['risk_balance' => $connection->raw('risk_balance * -1')]);

        $this->recomputeSnapshots($connection);
    }

    public function down(): void {
        $connection = DB::connection('tenant');

        $connection->table('agent_balance_histories')
            ->whereIn('trigger_type', self::FLIPPED_TRIGGERS)
            ->update(['amount' => $connection->raw('amount * -1')]);

        $connection->table('agent_commissions')
            ->update(['risk_balance' => $connection->raw('risk_balance * -1')]);

        $this->recomputeSnapshots($connection);
    }

    /**
     * Replays each agent's balance-ledger rows in id order to rebuild the
     * running `available_balance` snapshot, the agent's `balance`, and the
     * (soon-to-be-removed) due mirror. Uses the query builder throughout so no
     * model observers fire during the data migration.
     */
    private function recomputeSnapshots(Connection $connection): void {
        // The due mirror only needs refreshing while those columns still exist
        // (the follow-up migration drops them); guarding keeps this safe to run
        // regardless of ordering.
        $historyHasDue = Schema::connection('tenant')->hasColumn('agent_balance_histories', 'due_to_supplier');
        $agentHasDue = Schema::connection('tenant')->hasColumn('agents', 'due_to_energy_supplier');

        $agentIds = $connection->table('agent_balance_histories')->distinct()->pluck('agent_id');

        foreach ($agentIds as $agentId) {
            $rows = $connection->table('agent_balance_histories')
                ->where('agent_id', $agentId)
                ->orderBy('id')
                ->get(['id', 'trigger_type', 'amount']);

            $runningBalance = 0.0;
            foreach ($rows as $row) {
                if (in_array($row->trigger_type, self::BALANCE_TRIGGERS, true)) {
                    $runningBalance += (float) $row->amount;
                }
                $historyUpdate = ['available_balance' => $runningBalance];
                if ($historyHasDue) {
                    $historyUpdate['due_to_supplier'] = max(0.0, $runningBalance);
                }
                $connection->table('agent_balance_histories')->where('id', $row->id)->update($historyUpdate);
            }

            $agentUpdate = ['balance' => $runningBalance];
            if ($agentHasDue) {
                $agentUpdate['due_to_energy_supplier'] = max(0.0, $runningBalance);
            }
            $connection->table('agents')->where('id', $agentId)->update($agentUpdate);
        }
    }
};
