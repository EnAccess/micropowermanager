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
        Schema::connection('tenant')->create('e_bikes', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->integer('asset_id')->unsigned();
            $table->integer('manufacturer_id')->unsigned();
            $table->string('receive_time')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->double('speed')->nullable();
            $table->double('mileage')->nullable();
            $table->string('status')->nullable();
            $table->string('soh')->nullable();
            $table->double('battery_level')->nullable();
            $table->double('battery_voltage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('e_bikes');
    }
};
