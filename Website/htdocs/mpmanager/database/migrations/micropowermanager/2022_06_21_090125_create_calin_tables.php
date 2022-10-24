<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        if (!Schema:: hasTable('calin_api_credentials')) {
            Schema::connection('shard')->create('calin_api_credentials', static function (Blueprint
            $table) {
                $table->increments('id');
                $table->string('api_url')->default('http://api.calinhost.com/api');
                $table->string('user_id')->nullable();
                $table->string('api_key')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema:: hasTable('calin_transactions')) {
            Schema::connection('shard')->create('calin_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::connection('shard')->dropIfExists('calin_api_credentials');
        Schema::connection('shard')->dropIfExists('calin_transactions');

    }
};
