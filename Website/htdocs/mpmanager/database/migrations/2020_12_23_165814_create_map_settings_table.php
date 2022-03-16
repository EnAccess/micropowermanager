<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('zoom');
            $table->double('latitude',10);
            $table->double('longitude',10);
            $table->string('provider');
            $table->string('bingMapApiKey');
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
        Schema::dropIfExists('map_settings');
    }
}
