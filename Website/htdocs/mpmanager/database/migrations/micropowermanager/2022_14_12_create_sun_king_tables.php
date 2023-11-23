<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::connection('shard')->hasTable('sun_king_api_credentials')) {
            Schema::connection('shard')->create('sun_king_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('auth_url')->default('https://auth.central.glpapps.com/auth/realms/glp-dev/protocol/openid-connect/token');
                $table->string('api_url')->default(config('services.sunKing.url'));
                $table->string('client_id')->nullable();
                $table->string('client_secret')->nullable();
                $table->text('access_token')->nullable();
                $table->unsignedBigInteger('token_expires_in')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::connection('shard')->hasTable('sun_king_transactions')) {
            Schema::connection('shard')->create('sun_king_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::connection('shard')->dropIfExists('sun_king_api_credentials');
        Schema::connection('shard')->dropIfExists('sun_king_transactions');
    }
};
