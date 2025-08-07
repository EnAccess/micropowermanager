<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->table('sun_king_api_credentials', function (Blueprint $table) {
            $table->string('auth_url')->default(config('services.sunKing.default_auth_url'))->change();
            $table->string('api_url')->default(config('services.sunKing.default_api_url'))->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('sun_king_api_credentials', function (Blueprint $table) {
            $table->string('auth_url')->default('https://auth.central.glpapps.com/auth/realms/glp-dev/protocol/openid-connect/token')->change();
            $table->string('api_url')->default(config('services.sunKing.url'))->change();
        });
    }
};
