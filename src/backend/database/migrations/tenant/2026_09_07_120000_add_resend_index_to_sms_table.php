<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds an index to support the sms:resend-rejected lookup, which runs once per tenant per minute.
     */
    public function up(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->index(['status', 'direction', 'attempts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->dropIndex(['status', 'direction', 'attempts']);
        });
    }
};
