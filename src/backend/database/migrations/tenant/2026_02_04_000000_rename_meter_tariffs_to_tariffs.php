<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('meter_tariffs') && !Schema::connection('tenant')->hasTable('tariffs')) {
            Schema::connection('tenant')->rename('meter_tariffs', 'tariffs');
        }
    }

    public function down(): void {
        if (Schema::connection('tenant')->hasTable('tariffs') && !Schema::connection('tenant')->hasTable('meter_tariffs')) {
            Schema::connection('tenant')->rename('tariffs', 'meter_tariffs');
        }
    }
};
