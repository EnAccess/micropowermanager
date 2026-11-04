<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('openpaygo_issuance_reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('credit_days')->nullable()->after('generator_value');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('openpaygo_issuance_reservations', function (Blueprint $table) {
            $table->dropColumn('credit_days');
        });
    }
};
