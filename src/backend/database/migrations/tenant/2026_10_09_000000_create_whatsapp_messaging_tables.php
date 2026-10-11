<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_credentials')) {
            Schema::connection('tenant')->create('whatsapp_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->text('access_token')->nullable();
                $table->text('app_secret')->nullable();
                $table->text('webhook_verify_token');
                $table->string('phone_number_id')->nullable();
                $table->string('business_account_id')->nullable();
                $table->string('graph_api_version')->default('v26.0');
                $table->boolean('sms_fallback_enabled')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('whatsapp_contacts')) {
            Schema::connection('tenant')->create('whatsapp_contacts', static function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('person_id')->nullable()->index();
                $table->string('phone')->unique();
                $table->string('wa_id')->nullable();
                $table->string('opt_in_status');
                $table->timestamp('opted_in_at')->nullable();
                $table->timestamp('opted_out_at')->nullable();
                $table->timestamp('last_inbound_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('whatsapp_consent_logs')) {
            Schema::connection('tenant')->create('whatsapp_consent_logs', static function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('whatsapp_contact_id')->index();
                $table->string('action');
                $table->string('source');
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->text('raw_message')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            Schema::connection('tenant')->create('whatsapp_messages', static function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sms_id')->index();
                $table->unsignedInteger('whatsapp_contact_id')->nullable()->index();
                $table->string('wamid')->nullable()->unique();
                $table->string('message_type');
                $table->string('template_name')->nullable();
                $table->string('template_language')->nullable();
                $table->string('status');
                $table->integer('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('pricing_category')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('whatsapp_templates')) {
            Schema::connection('tenant')->create('whatsapp_templates', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('body_parser');
                $table->string('template_name');
                $table->string('language_code')->default('en');
                $table->json('parameters');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['body_parser', 'language_code']);
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('whatsapp_templates');
        Schema::connection('tenant')->dropIfExists('whatsapp_messages');
        Schema::connection('tenant')->dropIfExists('whatsapp_consent_logs');
        Schema::connection('tenant')->dropIfExists('whatsapp_contacts');
        Schema::connection('tenant')->dropIfExists('whatsapp_credentials');
    }
};
