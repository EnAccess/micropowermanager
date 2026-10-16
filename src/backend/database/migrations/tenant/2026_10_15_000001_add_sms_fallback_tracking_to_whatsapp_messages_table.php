<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            return;
        }

        Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table): void {
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'fallback_sms_id')) {
                $table->unsignedInteger('fallback_sms_id')->nullable()->after('failed_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'fallback_at')) {
                $table->timestamp('fallback_at')->nullable()->after('fallback_sms_id');
                $table->index(['fallback_at', 'status'], 'whatsapp_messages_fallback_scan_index');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'fallback_reason')) {
                $table->string('fallback_reason', 32)->nullable()->after('fallback_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'fallback_attempts')) {
                $table->unsignedTinyInteger('fallback_attempts')->default(0)->after('fallback_reason');
            }
        });
    }

    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            return;
        }

        Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasIndex('whatsapp_messages', 'whatsapp_messages_fallback_scan_index')) {
                $table->dropIndex('whatsapp_messages_fallback_scan_index');
            }
            foreach (['fallback_sms_id', 'fallback_at', 'fallback_reason', 'fallback_attempts'] as $column) {
                if (Schema::connection('tenant')->hasColumn('whatsapp_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
