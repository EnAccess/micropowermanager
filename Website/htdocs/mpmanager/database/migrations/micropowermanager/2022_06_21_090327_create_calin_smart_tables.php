<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        if (!Schema:: hasTable('calin_smart_api_credentials')) {
            Schema::connection('shard')->create('calin_smart_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('https://ami.calinhost.com/api');
                $table->string('company_name')->nullable();
                $table->string('user_name')->nullable();
                $table->string('password')->nullable();
                $table->string('password_vend')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema:: hasTable('calin_smart_transactions')) {
            Schema::connection('shard')->create('calin_smart_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::connection('shard')->dropIfExists('calin_smart_api_credentials');
        Schema::connection('shard')->dropIfExists('calin_smart_transactions');

    }
};
