<?php

use Carbon\Carbon;
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
        Schema::connection('tenant')->dropIfExists('p_v_s');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('p_v_s', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('mini_grid_id');
            $table->integer('node_id');
            $table->string('device_id');
            $table->double('daily');
            $table->string('daily_unit');
            $table->double('total');
            $table->string('total_unit');
            $table->double('new_generated_energy');
            $table->string('new_generated_energy_unit');
            $table->timestamps();
        });

        Schema::connection('tenant')->table('p_v_s', function (Blueprint $table) {
            $table->double('max_theoretical_output')->default(0);
            $table->dateTime('reading_date')->default(Carbon::now()->format('Y-m-d H:i:s'));
        });
    }
};
