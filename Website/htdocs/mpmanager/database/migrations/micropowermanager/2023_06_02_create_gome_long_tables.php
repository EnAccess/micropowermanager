<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        if (!Schema:: hasTable('gome_long_api_credentials')) {
            Schema::create('gome_long_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('http://60.205.216.142:8085/api/EKPower');
                $table->string('user_id')->nullable();
                $table->string('user_password')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema:: hasTable('gome_long_transactions')) {
            Schema::create('gome_long_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }

        if (!Schema:: hasTable('gome_long_tariffs')) {
            Schema::create('gome_long_tariffs', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('tariff_id')->unique();
                $table->integer('mpm_tariff_id')->unique(); // Tariff ID corresponding tariff id in MPM
                $table->string('vat')->nullable();
                $table->timestamps();
            });
        }



    }

    public function down()
    {
        Schema::dropIfExists('gome_long_api_credentials');
        Schema::dropIfExists('gome_long_transactions');
        Schema::dropIfExists('gome_long_tariffs');

    }
};
