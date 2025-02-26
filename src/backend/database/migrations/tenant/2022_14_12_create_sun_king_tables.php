<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::connection('tenant')->hasTable('sun_king_api_credentials')) {
            Schema::connection('tenant')->create('sun_king_api_credentials', static function (Blueprint $table) {
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
        if (!Schema::connection('tenant')->hasTable('sun_king_transactions')) {
            Schema::connection('tenant')->create('sun_king_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('sun_king_api_credentials');
        Schema::connection('tenant')->dropIfExists('sun_king_transactions');
    }
};
