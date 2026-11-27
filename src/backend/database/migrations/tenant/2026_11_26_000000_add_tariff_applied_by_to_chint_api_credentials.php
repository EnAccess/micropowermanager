<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('chint_api_credentials', function (Blueprint $table) {
            $table->string('tariff_applied_by')->default('chint_platform')->after('user_password');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('chint_api_credentials', function (Blueprint $table) {
            $table->dropColumn('tariff_applied_by');
        });
    }
};
