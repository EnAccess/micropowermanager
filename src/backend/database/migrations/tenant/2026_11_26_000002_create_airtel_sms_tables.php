<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('airtel_sms_credentials', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('system_id')->nullable();
            $table->text('password')->nullable();
            $table->string('source_address')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('airtel_sms_messages', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sms_id')->index();
            $table->string('message_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('airtel_sms_credentials');
        Schema::connection('tenant')->dropIfExists('airtel_sms_messages');
    }
};
