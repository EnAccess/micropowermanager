<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->boolean('upcoming_reminder_enabled')->default(false)->after('remind_rate');
            $table->boolean('overdue_reminder_enabled')->default(false)->after('upcoming_reminder_enabled');
        });

        DB::connection('tenant')->table('sms_appliance_remind_rates')->update([
            'upcoming_reminder_enabled' => DB::raw('enabled'),
            'overdue_reminder_enabled' => DB::raw('enabled'),
        ]);

        Schema::connection('tenant')->table('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });

        DB::connection('tenant')->table('appliance_rates')->where('remind', '>=', 1)->update(['remind' => 2]);
    }

    public function down(): void {
        DB::connection('tenant')->table('appliance_rates')->where('remind', 1)->update(['remind' => 0]);
        DB::connection('tenant')->table('appliance_rates')->where('remind', 2)->update(['remind' => 1]);

        Schema::connection('tenant')->table('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->boolean('enabled')->default(false)->after('remind_rate');
        });

        DB::connection('tenant')->table('sms_appliance_remind_rates')->update([
            'enabled' => DB::raw('upcoming_reminder_enabled OR overdue_reminder_enabled'),
        ]);

        Schema::connection('tenant')->table('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->dropColumn(['upcoming_reminder_enabled', 'overdue_reminder_enabled']);
        });
    }
};
