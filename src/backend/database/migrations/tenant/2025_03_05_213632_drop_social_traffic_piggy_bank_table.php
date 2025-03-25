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
        Schema::connection('tenant')->dropIfExists('social_tariff_piggy_banks');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('social_tariff_piggy_banks', function (Blueprint $table) {
            $table->id();
            $table->integer('savings');
            $table->integer('meter_parameter_id');
            $table->integer('social_tariff_id');
            $table->timestamps();
        });
    }
};
