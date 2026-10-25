<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the Migration.
     */
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_credentials')) {
            Schema::connection('tenant')->create(
                'whatsapp_credentials',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('phone_number_id')->nullable();
                    $table->string('whatsapp_business_account_id')->nullable();
                    $table->text('api_access_token')->nullable();
                    $table->string('sender_phone_number')->nullable();
                    $table->text('webhook_verify_token')->nullable();
                    $table->string('api_version')->default('v21.0');
                    $table->boolean('is_enabled')->default(false);
                    $table->timestamps();
                }
            );
        }

        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            Schema::connection('tenant')->create(
                'whatsapp_messages',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('message_id')->nullable()->index();
                    $table->string('recipient_phone');
                    $table->string('notification_type');
                    $table->string('status')->default('queued');
                    $table->text('body')->nullable();
                    $table->text('error_message')->nullable();
                    $table->timestamp('sent_at')->nullable();
                    $table->timestamp('delivered_at')->nullable();
                    $table->timestamp('read_at')->nullable();
                    $table->timestamps();
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_messages');
        Schema::connection('tenant')->dropIfExists('whatsapp_credentials');
    }
};
