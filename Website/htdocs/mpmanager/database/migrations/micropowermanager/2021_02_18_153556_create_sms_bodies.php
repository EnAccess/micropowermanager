<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('shard')->create('sms_bodies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference',50)->unique();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->string('place_holder');
            $table->string('variables');
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
        Schema::connection('shard')->dropIfExists('sms_bodies');
    }
};
