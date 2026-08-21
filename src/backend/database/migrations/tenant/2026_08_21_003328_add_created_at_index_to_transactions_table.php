<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * The operator dashboard groups a tenant's transactions by month and payment
     * provider, which without this index scans the largest table in every tenant
     * database on every rebuild.
     */
    public function up(): void {
        Schema::connection('tenant')->table('transactions', function (Blueprint $table) {
            $table->index(['created_at', 'original_transaction_type'], 'transactions_created_at_provider_index');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_created_at_provider_index');
        });
    }
};
