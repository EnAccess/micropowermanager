<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('paystack_credentials')) {
            Schema::connection('tenant')->table('paystack_credentials', function (Blueprint $table) {
                if (!Schema::connection('tenant')->hasColumn('paystack_credentials', 'merchant_email')) {
                    $table->string('merchant_email')->nullable()->after('merchant_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (Schema::connection('tenant')->hasTable('paystack_credentials')) {
            Schema::connection('tenant')->table('paystack_credentials', function (Blueprint $table) {
                if (Schema::connection('tenant')->hasColumn('paystack_credentials', 'merchant_email')) {
                    $table->dropColumn('merchant_email');
                }
            });
        }
    }
};
