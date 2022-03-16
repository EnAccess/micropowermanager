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
        Schema::connection('micropowermanager')->create('access_rate_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('meter_id');
            $table->integer('access_rate_id');
            $table->dateTime('due_date');
            $table->integer('debt')->default(0);
            $table->integer('unpaid_in_row')->default(0);
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
        Schema::connection('micropowermanager')->dropIfExists('access_rate_payments');
    }
};
