<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('africas_talking_credentials')) {
            Schema::connection('tenant')->create('africas_talking_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_key')->nullable();
                $table->string('username')->nullable();
                $table->string('short_code')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('africas_talking_messages')) {
            Schema::connection('tenant')->create('africas_talking_messages', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sms_id');
                $table->string('message_id');
                $table->string('status');
                $table->integer('status_code');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('africas_talking_credentials');
        Schema::connection('tenant')->dropIfExists('africas_talking_messages');
    }
};
