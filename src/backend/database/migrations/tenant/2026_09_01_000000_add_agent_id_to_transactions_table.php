<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which agent initiated a transaction, so a payment an agent pushed through a payment
 * provider can still be attributed to them and earn their commission. Cash payments keep
 * expressing that link through their AgentTransaction, so this stays null for those.
 */
return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('agent_id')->nullable()->after('original_transaction_type')->index();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('transactions', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropColumn('agent_id');
        });
    }
};
