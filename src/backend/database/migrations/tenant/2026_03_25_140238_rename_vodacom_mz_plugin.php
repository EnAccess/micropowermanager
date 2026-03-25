<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->rename(
            'vodacom_mobile_money_transactions',
            'vodacom_mz_transactions'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->rename(
            'vodacom_mz_transactions',
            'vodacom_mobile_money_transactions'
        );
    }
};
