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
        Schema::connection('tenant')->table('batteries', function (Blueprint $table) {
            $table->boolean('active')->default(0);
            $table->double('c_total')->default(0);
            $table->string('c_total_unit')->default(0);
            $table->double('c_newly_energy')->default(0);
            $table->string('c_newly_energy_unit')->default('Wh');
            $table->double('temperature_min')->default(0);
            $table->double('temperature_max')->default(0);
            $table->double('temperature_average')->default(0);
            $table->string('temperature_unit')->default('°C');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('batteries', function (Blueprint $table) {});
    }
};
