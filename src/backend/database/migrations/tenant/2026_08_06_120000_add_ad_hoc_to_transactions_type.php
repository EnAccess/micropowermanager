<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->statement(
            "ALTER TABLE transactions MODIFY COLUMN type ENUM('energy','deferred_payment','eaas_rate','down_payment','ad_hoc','unknown','imported','3rd party api sync') DEFAULT 'unknown'"
        );
    }

    public function down(): void {
        DB::connection('tenant')->statement(
            "UPDATE transactions SET type = 'energy' WHERE type = 'ad_hoc'"
        );
        DB::connection('tenant')->statement(
            "ALTER TABLE transactions MODIFY COLUMN type ENUM('energy','deferred_payment','eaas_rate','down_payment','unknown','imported','3rd party api sync') DEFAULT 'unknown'"
        );
    }
};
