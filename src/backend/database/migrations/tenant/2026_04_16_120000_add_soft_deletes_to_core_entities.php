<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = ['clusters', 'mini_grids', 'cities'];

    public function up(): void {
        foreach (self::TABLES as $table) {
            Schema::connection('tenant')->table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void {
        foreach (self::TABLES as $table) {
            Schema::connection('tenant')->table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
