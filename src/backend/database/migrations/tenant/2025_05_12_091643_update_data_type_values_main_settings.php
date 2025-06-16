<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->double('vat_energy')->change();
            $table->double('vat_appliance')->change();
        });

        // For MySQL Laravel's `float(5)` gets translated to DOUBLE(5,2).
        // As a result changing it to `double` using Laravel might not have an effect.
        // Running a SQL statement here to be sure.
        DB::connection('tenant')->statement('ALTER TABLE main_settings MODIFY vat_energy DOUBLE');
        DB::connection('tenant')->statement('ALTER TABLE main_settings MODIFY vat_appliance DOUBLE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->float('vat_energy', 5)->change();
            $table->float('vat_appliance', 5)->change();
        });

        DB::connection('tenant')->statement('ALTER TABLE main_settings MODIFY vat_energy DOUBLE(5,2)');
        DB::connection('tenant')->statement('ALTER TABLE main_settings MODIFY vat_appliance DOUBLE(5,2)');
    }
};
