<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('micro_power_manager')->table('mpm_plugins', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
        });
    }

    public function down(): void {
        Schema::connection('micro_power_manager')->table('mpm_plugins', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
