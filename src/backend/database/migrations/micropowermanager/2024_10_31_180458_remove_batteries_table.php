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
        Schema::connection('tenant')->dropIfExists('batteries');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('batteries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mini_grid_id');
            $table->integer('node_id');
            $table->string('device_id');
            $table->timestamp('read_out');
            $table->integer('battery_count');

            $table->double('soc_average');
            $table->string('soc_unit');
            $table->double('soc_min');
            $table->double('soc_max');

            $table->double('soh_average');
            $table->string('soh_unit');
            $table->double('soh_min');
            $table->double('soh_max');

            $table->double('d_total');
            $table->string('d_total_unit');
            $table->double('d_newly_energy');
            $table->string('d_newly_energy_unit');

            $table->timestamps();
        });

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
};
