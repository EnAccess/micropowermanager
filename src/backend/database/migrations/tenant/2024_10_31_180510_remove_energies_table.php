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
        Schema::connection('tenant')->dropIfExists('energies');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('energies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mini_grid_id');
            $table->string('meter_id');
            $table->tinyInteger('active');
            $table->integer('node_id');
            $table->string('device_id');
            $table->double('total_energy');
            $table->float('used_energy_since_last');
            $table->string('total_absorbed_unit');
            $table->float('total_absorbed');
            $table->float('absorbed_energy_since_last');
            $table->string('absorbed_energy_since_last_unit');
            $table->timestamp('read_out');
            $table->timestamps();
        });

        Schema::connection('tenant')->table('energies', function (Blueprint $table) {
            $table->double('used_energy_since_last')->default(0)->change();
            $table->double('total_absorbed')->default(0)->change();
            $table->double('absorbed_energy_since_last')->default(0)->change();
        });

        Schema::connection('tenant')->table('energies', function (Blueprint $table) {
            $table->double('used_energy_since_last')->default(0)->change();
            $table->double('total_absorbed')->default(0)->change();
            $table->double('absorbed_energy_since_last')->default(0)->change();
        });
    }
};
