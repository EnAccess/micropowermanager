<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Backfill device_id for tokens that have NULL device_id but can be inferred
     * from their related transaction's message (device_serial).
     *
     * @return void
     */
    public function up() {
        $connection = DB::connection('tenant');

        // Get token IDs and their corresponding device IDs for time-based tokens
        $tokenDevicePairs = $connection->table('tokens')
            ->join('transactions', 'tokens.transaction_id', '=', 'transactions.id')
            ->join('devices', 'devices.device_serial', '=', 'transactions.message')
            ->whereNull('tokens.device_id')
            ->where('tokens.token_type', '=', 'time')
            ->select('tokens.id', 'devices.id as device_id')
            ->get();

        // Group by device_id for batch updates
        $updatesByDevice = $tokenDevicePairs->groupBy('device_id');

        // Update tokens in batches grouped by device_id
        foreach ($updatesByDevice as $deviceId => $pairs) {
            $tokenIds = $pairs->pluck('id');
            $connection->table('tokens')
                ->whereIn('id', $tokenIds)
                ->update(['device_id' => $deviceId]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Revert device_id to NULL for tokens that were updated by this migration.
     * Note: This will only revert tokens that have a matching transaction->device relationship.
     *
     * @return void
     */
    public function down() {
        $connection = DB::connection('tenant');

        // Get token IDs that match the criteria for time-based tokens
        $tokenIds = $connection->table('tokens')
            ->join('transactions', 'tokens.transaction_id', '=', 'transactions.id')
            ->join('devices', 'devices.device_serial', '=', 'transactions.message')
            ->whereColumn('devices.id', 'tokens.device_id')
            ->where('tokens.token_type', '=', 'time')
            ->pluck('tokens.id');

        // Update tokens in batches
        if ($tokenIds->isNotEmpty()) {
            $connection->table('tokens')
                ->whereIn('id', $tokenIds)
                ->update(['device_id' => null]);
        }
    }
};
