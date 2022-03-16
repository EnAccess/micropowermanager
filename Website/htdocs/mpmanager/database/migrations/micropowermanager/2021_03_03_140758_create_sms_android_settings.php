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
        Schema::connection('micropowermanager')->create('sms_android_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url')->default('https://fcm.googleapis.com/fcm/send');
            $table->string('token')->nullable();
            $table->string('key')->nullable();
            $table->string('callback')->default('https://mpmanager.local/api/sms/%s/confirm');
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
        Schema::connection('micropowermanager')->dropIfExists('sms_android_settings');
    }
};
