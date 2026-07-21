<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the "due to company" columns made redundant by the single signed
 * balance (a positive balance already is the money owed to the company).
 *
 * Split from the sign-flip migration so the flip can be rolled back on its own
 * before these columns disappear. The down() rebuilds the columns and refills
 * them from the current balance.
 */
return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('agents', function (Blueprint $table) {
            $table->dropColumn('due_to_energy_supplier');
        });
        Schema::connection('tenant')->table('agent_balance_histories', function (Blueprint $table) {
            $table->dropColumn('due_to_supplier');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('agents', function (Blueprint $table) {
            $table->double('due_to_energy_supplier')->default(0);
        });
        Schema::connection('tenant')->table('agent_balance_histories', function (Blueprint $table) {
            $table->double('due_to_supplier')->default(0);
        });

        $connection = DB::connection('tenant');
        $connection->table('agents')
            ->update(['due_to_energy_supplier' => $connection->raw('GREATEST(balance, 0)')]);
        $connection->table('agent_balance_histories')
            ->update(['due_to_supplier' => $connection->raw('GREATEST(available_balance, 0)')]);
    }
};
