<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('whatsapp_credentials', function (Blueprint $table) {
            $table->text('app_secret')->nullable();
            $table->string('webhook_verify_token')->nullable();
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->text('error')->nullable();
        });

        Schema::create('whatsapp_inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wamid')->unique();
            $table->string('phone')->index();
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('whatsapp_inbound_messages');

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('error');
        });

        Schema::table('whatsapp_credentials', function (Blueprint $table) {
            $table->dropColumn(['app_secret', 'webhook_verify_token']);
        });
    }
};
