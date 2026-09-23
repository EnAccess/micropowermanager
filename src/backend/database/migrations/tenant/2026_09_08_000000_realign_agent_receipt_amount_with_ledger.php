<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * agent_receipts.amount now holds the amount collected that a receipt settles,
 * where it used to hold the cash handed over. The balance ledger already deducted
 * the cash plus the commission credited at the visit, so adding the commission
 * back onto the amount makes each receipt agree with what was taken off the
 * balance. Receipts predating the commission_credited column coalesce to 0 and
 * stay as they are: the observer of that era deducted the amount alone.
 */
return new class extends Migration {
    public function up(): void {
        $connection = DB::connection('tenant');

        $connection->transaction(function () use ($connection) {
            // Aggregated because detail is a hasMany; a bare join would let MySQL
            // pick one row of a multi-row receipt arbitrarily.
            $commissionPerReceipt = $connection->table('agent_receipt_details')
                ->selectRaw('agent_receipt_id, SUM(COALESCE(commission_credited, 0)) as commission_credited')
                ->groupBy('agent_receipt_id');

            $connection->table('agent_receipts')
                ->joinSub($commissionPerReceipt, 'detail', 'detail.agent_receipt_id', '=', 'agent_receipts.id')
                ->update([
                    'amount' => $connection->raw('agent_receipts.amount + detail.commission_credited'),
                ]);

            $connection->table('agent_receipt_details')
                ->whereNull('commission_credited')
                ->update(['commission_credited' => 0]);
        });
    }

    // Irreversible: once amount is a single figure the split cannot be recovered.
    public function down(): void {}
};
