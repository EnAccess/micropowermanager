<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->text('error_message')->nullable();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
