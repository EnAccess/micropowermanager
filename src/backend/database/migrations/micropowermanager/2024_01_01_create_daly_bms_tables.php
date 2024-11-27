<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('daly_bms_api_credentials')) {
            Schema::create('daly_bms_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('https://dev.iov18.com/api');
                $table->string('user_name')->nullable();
                $table->string('password')->nullable();
                $table->text('access_token')->nullable();
                $table->unsignedBigInteger('token_expires_in')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('daly_bms_transactions')) {
            Schema::create('daly_bms_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::dropIfExists('daly_bms_api_credentials');
        Schema::dropIfExists('daly_bms_transactions');
    }
};
