<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->unsignedInteger('down_payment_max_token_days')->nullable();
        });
    }

    public function down(): void {
        if (Schema::connection('tenant')->hasColumn('main_settings', 'down_payment_max_token_days')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
                $table->dropColumn('down_payment_max_token_days');
            });
        }
    }
};
