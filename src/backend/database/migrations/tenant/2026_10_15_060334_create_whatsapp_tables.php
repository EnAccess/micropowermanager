<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_credentials', function (Blueprint $table) {
            $table->id();
            $table->text('access_token')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('graph_version')->default('v23.0');
            $table->string('template_language')->default('en');
            $table->string('receipt_template')->default('mpm_payment_notice');
            $table->string('reminder_template')->default('mpm_payment_reminder');
            $table->boolean('fallback_to_sms')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sms_id')->index();
            $table->string('message_id')->nullable()->index();
            $table->string('template_name')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('whatsapp_opt_ins', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->timestamp('opted_in_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('source')->default('operator');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('whatsapp_opt_ins');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_credentials');
    }
};
