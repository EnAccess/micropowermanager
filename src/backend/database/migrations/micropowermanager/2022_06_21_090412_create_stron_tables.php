<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('stron_api_credentials')) {
            Schema::connection('tenant')->create('stron_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('http://www.saitecapi.stronpower.com/api');
                $table->string('api_token')->nullable();
                $table->string('company_name')->nullable();
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->boolean('is_authenticated')->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('stron_transactions')) {
            Schema::connection('tenant')->create('stron_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('stron_api_credentials');
        Schema::connection('tenant')->dropIfExists('stron_transactions');
    }
};
