<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('logs')) {
            return;
        }

        DB::connection('tenant')
            ->table('logs')
            ->where('affected_type', 'App\\Models\\AssetPerson')
            ->update(['affected_type' => 'App\\Models\\AppliancePerson']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('logs')) {
            return;
        }

        DB::connection('tenant')
            ->table('logs')
            ->where('affected_type', 'App\\Models\\AppliancePerson')
            ->update(['affected_type' => 'App\\Models\\AssetPerson']);
    }
};
