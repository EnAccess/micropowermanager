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
        Schema::connection('shard')->create('meter_parameters', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('owner');
            $table->integer('meter_id');
            $table->integer('connection_type_id');
            $table->integer('connection_group_id');
            $table->integer('tariff_id');
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
        Schema::connection('shard')->dropIfExists('meter_parameters');
    }
};
