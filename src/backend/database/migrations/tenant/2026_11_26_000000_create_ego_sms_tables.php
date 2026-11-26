<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('ego_sms_credentials', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('sender_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('ego_sms_messages', static function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('sms_id')->index();
            $table->string('follow_up_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('ego_sms_credentials');
        Schema::connection('tenant')->dropIfExists('ego_sms_messages');
    }
};
