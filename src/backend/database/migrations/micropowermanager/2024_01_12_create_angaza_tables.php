<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::connection('shard')->hasTable('angaza_api_credentials')) {
            Schema::connection('shard')->create('angaza_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('https://payg.angazadesign.com/nexus/v1');
                $table->string('client_id')->nullable();
                $table->string('client_secret')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::connection('shard')->hasTable('angaza_transactions')) {
            Schema::connection('shard')->create('angaza_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::connection('shard')->dropIfExists('angaza_api_credentials');
        Schema::connection('shard')->dropIfExists('angaza_transactions');
    }
};
