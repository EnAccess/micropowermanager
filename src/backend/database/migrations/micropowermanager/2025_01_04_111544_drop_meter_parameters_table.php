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
        Schema::connection('tenant')->dropIfExists('meter_parameters');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('meter_parameters', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('owner');
            $table->integer('meter_id');
            $table->integer('connection_type_id');
            $table->integer('connection_group_id');
            $table->integer('tariff_id');
            $table->timestamps();
        });
    }
};
