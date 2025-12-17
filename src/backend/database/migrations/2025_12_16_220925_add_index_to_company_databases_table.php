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
    public function up(): void {
        Schema::connection('micro_power_manager')->table('company_databases', function (Blueprint $table) {
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        Schema::connection('micro_power_manager')->table('company_databases', function (Blueprint $table) {
            $table->dropUnique(['company_id']);
        });
    }
};
