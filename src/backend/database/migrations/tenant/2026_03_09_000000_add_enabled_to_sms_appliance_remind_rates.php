<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->boolean('enabled')->default(false)->after('remind_rate');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
};
