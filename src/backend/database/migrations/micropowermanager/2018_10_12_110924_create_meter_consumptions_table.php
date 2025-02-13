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
        Schema::connection('tenant')->create('meter_consumptions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('meter_id');
            $table->double('total_consumption');
            $table->double('daily_consumption');
            $table->double('credit_on_meter');
            $table->date('reading_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('meter_consumptions');
    }
};
