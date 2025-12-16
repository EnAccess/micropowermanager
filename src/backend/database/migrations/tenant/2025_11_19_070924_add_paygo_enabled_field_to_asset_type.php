<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->table('asset_types', function (Blueprint $table) {
            $table->boolean('paygo_enabled')
                ->default(false)
                ->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('asset_types', function (Blueprint $table) {
            $table->dropColumn('paygo_enabled');
        });
    }
};
