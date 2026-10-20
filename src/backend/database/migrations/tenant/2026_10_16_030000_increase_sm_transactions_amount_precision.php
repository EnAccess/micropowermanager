<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // decimal('amount') with no precision/scale defaults to decimal(8,2) — max 999999.99.
        // Real transaction amounts (e.g. in low-value currencies like TZS) routinely exceed
        // that and fail with "Out of range value for column 'amount'" on insert.
        Schema::connection('tenant')->table('sm_transactions', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('sm_transactions', function (Blueprint $table) {
            $table->decimal('amount')->nullable()->change();
        });
    }
};
