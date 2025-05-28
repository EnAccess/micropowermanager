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
        DB::connection('tenant')->statement('ALTER TABLE main_settings MODIFY vat_energy DOUBLE(5,2) NOT NULL');
        DB::connection('tenant')->statement('ALTER TABLE main_settings MODIFY vat_appliance DOUBLE(5,2) NOT NULL');
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
    }
};
