<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('calin_smart_api_credentials')) {
            Schema::connection('tenant')->create('calin_smart_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('https://ami.calinhost.com/api');
                $table->string('company_name')->nullable();
                $table->string('user_name')->nullable();
                $table->string('password')->nullable();
                $table->string('password_vend')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('calin_smart_transactions')) {
            Schema::connection('tenant')->create('calin_smart_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('calin_smart_api_credentials');
        Schema::connection('tenant')->dropIfExists('calin_smart_transactions');
    }
};
