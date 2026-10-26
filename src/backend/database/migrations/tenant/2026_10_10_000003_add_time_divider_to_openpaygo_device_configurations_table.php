<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('openpaygo_device_configurations', function (Blueprint $table) {
            $table->unsignedTinyInteger('time_divider')->nullable()->after('starting_code');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('openpaygo_device_configurations', function (Blueprint $table) {
            $table->dropColumn('time_divider');
        });
    }
};
