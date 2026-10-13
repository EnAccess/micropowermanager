<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasColumn('whatsapp_credentials', 'sms_unit_cost')) {
            Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
                $table->decimal('sms_unit_cost', 10, 4)->nullable();
                $table->decimal('whatsapp_utility_cost', 10, 4)->nullable();
                $table->string('cost_currency', 3)->nullable();
            });
        }

        if (!Schema::connection('tenant')->hasColumn('whatsapp_messages', 'billable')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                // Null until a status webhook carries Meta's pricing.billable flag.
                $table->boolean('billable')->nullable();
            });
        }
    }

    public function down(): void {
        if (Schema::connection('tenant')->hasColumn('whatsapp_messages', 'billable')) {
            Schema::connection('tenant')->table('whatsapp_messages', static function (Blueprint $table) {
                $table->dropColumn('billable');
            });
        }

        if (Schema::connection('tenant')->hasColumn('whatsapp_credentials', 'sms_unit_cost')) {
            Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
                $table->dropColumn(['sms_unit_cost', 'whatsapp_utility_cost', 'cost_currency']);
            });
        }
    }
};
