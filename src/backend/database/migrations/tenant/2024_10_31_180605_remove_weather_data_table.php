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
        Schema::connection('tenant')->dropIfExists('weather_data');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('weather_data', static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('solar_id');
            $table->string('current_weather_data');
            $table->string('forecast_weather_data');
            $table->dateTime('record_time')->nullable();
            $table->timestamps();
        });
    }
};
