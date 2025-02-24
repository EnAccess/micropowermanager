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
        Schema::connection('tenant')->create('social_tariffs', function (Blueprint $table) {
            $table->id();
            $table->integer('tariff_id');
            $table->integer('daily_allowance');
            $table->integer('price');
            $table->integer('initial_energy_budget');
            $table->integer('maximum_stacked_energy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('social_tariffs');
    }
};
