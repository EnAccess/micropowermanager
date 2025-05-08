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
        Schema::connection('tenant')->create('shs_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id')->unique();
            $table->integer('device_id');
            $table->string('token');
            $table->string('token_type')->default('time'); // 'time' or 'energy'
            $table->double('token_amount'); // days for time-based, kWh for energy-based
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('shs_tokens');
    }
};
