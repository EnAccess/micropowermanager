<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('vodacom_mz_transactions')) {
            Schema::connection('tenant')->table('vodacom_mz_transactions', function (Blueprint $table) {
                $table->integer('status')->change();
            });
        }
    }

    public function down(): void {
        if (Schema::connection('tenant')->hasTable('vodacom_mz_transactions')) {
            Schema::connection('tenant')->table('vodacom_mz_transactions', function (Blueprint $table) {
                $table->string('status')->change();
            });
        }
    }
};
