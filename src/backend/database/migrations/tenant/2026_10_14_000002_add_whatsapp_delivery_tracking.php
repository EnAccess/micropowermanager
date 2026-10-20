<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('whatsapp_messages', function (Blueprint $table): void {
            $table->dateTime('provider_sent_at')->nullable();
            $table->dateTime('provider_status_at')->nullable();
            $table->dateTime('delivery_status_updated_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('provider_error_code', 20)->nullable();
        });
        Schema::connection('tenant')->create('whatsapp_delivery_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->char('event_key', 64)->unique();
            $table->string('message_id')->index();
            $table->string('status', 32);
            $table->dateTime('provider_at');
            $table->string('error_code', 20)->nullable();
            $table->string('error_message')->nullable();
            $table->string('notification_type')->nullable();
            $table->string('state', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('next_attempt_at')->nullable();
            $table->dateTime('received_at');
            $table->dateTime('processed_at')->nullable();
            $table->string('last_error', 64)->nullable();
            $table->index(['state', 'next_attempt_at']);
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_delivery_events');
        Schema::connection('tenant')->table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropColumn(['provider_sent_at', 'provider_status_at', 'delivery_status_updated_at', 'failed_at', 'provider_error_code']);
        });
    }
};
