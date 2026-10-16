<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'person_id')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                $table->unsignedInteger('person_id')->nullable()->after('id');
            });
        }
        if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'transaction_id')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                $table->unsignedInteger('transaction_id')->nullable()->after('person_id');
            });
        }
        if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'template_id')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                $table->unsignedInteger('template_id')->nullable()->after('transaction_id');
            });
        }
        if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'recipient_name')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                $table->string('recipient_name')->nullable()->after('recipient_phone');
            });
        }
        if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'reference')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                $table->string('reference')->nullable()->after('notification_type')->index();
            });
        }

        if (!Schema::connection('tenant')->hasColumn('whatsapp_credentials', 'notification_settings')) {
            Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
                $table->json('notification_settings')->nullable()->after('is_enabled');
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
            $columns = [];
            if (Schema::connection('tenant')->hasColumn('whatsapp_messages', 'person_id')) {
                $columns[] = 'person_id';
            }
            if (Schema::connection('tenant')->hasColumn('whatsapp_messages', 'transaction_id')) {
                $columns[] = 'transaction_id';
            }
            if (Schema::connection('tenant')->hasColumn('whatsapp_messages', 'template_id')) {
                $columns[] = 'template_id';
            }
            if (Schema::connection('tenant')->hasColumn('whatsapp_messages', 'recipient_name')) {
                $columns[] = 'recipient_name';
            }
            if (Schema::connection('tenant')->hasColumn('whatsapp_messages', 'reference')) {
                $columns[] = 'reference';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('whatsapp_credentials', 'notification_settings')) {
                $table->dropColumn('notification_settings');
            }
        });
    }
};
