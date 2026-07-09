<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->string('manufacturer_mapping_status')->default('unknown');
            $table->timestamp('manufacturer_mapping_checked_at')->nullable();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->dropColumn(['manufacturer_mapping_status', 'manufacturer_mapping_checked_at']);
        });
    }
};
