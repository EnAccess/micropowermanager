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
        Schema::connection('shard')->create('sms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('receiver');
            $table->morphs('trigger');
            $table->text('body');
            $table->integer('status');
            $table->string('uuid');
            $table->integer('sender_id');
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
        Schema::connection('shard')->dropIfExists('sms');
    }
};
