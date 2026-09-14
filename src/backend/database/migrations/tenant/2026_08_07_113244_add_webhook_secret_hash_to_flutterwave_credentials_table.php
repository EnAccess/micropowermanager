<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('flutterwave_credentials')) {
            Schema::connection('tenant')->table('flutterwave_credentials', function (Blueprint $table) {
                if (!Schema::connection('tenant')->hasColumn('flutterwave_credentials', 'webhook_secret_hash')) {
                    $table->text('webhook_secret_hash')->nullable()->after('encryption_key');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (Schema::connection('tenant')->hasTable('flutterwave_credentials')) {
            Schema::connection('tenant')->table('flutterwave_credentials', function (Blueprint $table) {
                if (Schema::connection('tenant')->hasColumn('flutterwave_credentials', 'webhook_secret_hash')) {
                    $table->dropColumn('webhook_secret_hash');
                }
            });
        }
    }
};
