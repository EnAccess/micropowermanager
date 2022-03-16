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
        Schema::connection('micropowermanager')->create('asset_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('asset_person_id');
            $table->integer('rate_cost');
            $table->integer('remaining');
            $table->dateTime('due_date');
            $table->integer('remind');
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
        Schema::connection('micropowermanager')->dropIfExists('asset_rates');
    }
};
