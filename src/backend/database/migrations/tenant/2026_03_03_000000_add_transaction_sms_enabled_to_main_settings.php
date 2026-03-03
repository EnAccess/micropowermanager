<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->boolean('transaction_sms_enabled')->default(true);
        });
    }

    public function down(): void {
        if (Schema::connection('tenant')->hasColumn('main_settings', 'transaction_sms_enabled')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
                $table->dropColumn('transaction_sms_enabled');
            });
        }
    }
};
