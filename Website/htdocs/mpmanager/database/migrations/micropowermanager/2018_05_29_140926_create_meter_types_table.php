<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('micropowermanager')->dropIfExists('meter_types');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('micropowermanager')->create('meter_types', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('online')->default(false);
            $table->integer('phase')->default(1);
            $table->integer('max_current')->default(10);
            $table->timestamps();
        });
    }
};
