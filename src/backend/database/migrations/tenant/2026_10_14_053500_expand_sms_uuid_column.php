<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->string('uuid', 191)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->string('uuid', 50)->nullable()->change();
        });
    }
};
