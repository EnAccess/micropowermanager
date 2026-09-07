<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separate from the data migration that precedes it: MySQL commits DDL
 * implicitly, so combining the two would leave a failed drop with an applied
 * update that a re-run would double.
 */
return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('agent_receipt_details', function (Blueprint $table) {
            $table->double('commission_credited')->default(0)->nullable(false)->change();
            // collected is the receipt amount minus the commission credited, summary
            // is structurally zero once the amount is capped at the due, and earlier
            // only ever carried the previous row's summary.
            $table->dropColumn(['collected', 'summary', 'earlier']);
        });
    }

    // Irreversible: the dropped columns held no value that survives the change.
    public function down(): void {}
};
