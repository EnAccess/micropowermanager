<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('shard')->create('batteries', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('shard')->dropIfExists('batteries');
    }
};
