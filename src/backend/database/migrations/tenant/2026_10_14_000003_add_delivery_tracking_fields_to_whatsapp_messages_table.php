<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            return;
        }

        Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'provider_sent_at')) {
                $table->timestamp('provider_sent_at')->nullable()->after('sent_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'provider_status_at')) {
                $table->timestamp('provider_status_at')->nullable()->after('provider_sent_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'delivery_status_updated_at')) {
                $table->timestamp('delivery_status_updated_at')->nullable()->after('provider_status_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('delivery_status_updated_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('read_at');
            }
            if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'provider_error_code')) {
                $table->string('provider_error_code')->nullable()->after('failed_at');
            }
        });
    }

    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('whatsapp_messages')) {
            return;
        }

        Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
            $columns = [
                'provider_sent_at',
                'provider_status_at',
                'delivery_status_updated_at',
                'delivered_at',
                'read_at',
                'failed_at',
                'provider_error_code',
            ];
            foreach ($columns as $column) {
                if (Schema::connection('tenant')->hasColumn('whatsapp_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
