<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('chint_api_credentials')) {
            Schema::create('chint_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->default('https://197.221.155.210/jianpanbiaoEnglish/PrepaidWebService.asmx');
                $table->string('user_name')->nullable();
                $table->string('user_password')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chint_transactions')) {
            Schema::create('chint_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::dropIfExists('chint_api_credentials');
        Schema::dropIfExists('chint_transactions');
    }
};
