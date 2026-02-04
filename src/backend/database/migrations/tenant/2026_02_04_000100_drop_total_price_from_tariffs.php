<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('tariffs', function (Blueprint $table): void {
            if (Schema::connection('tenant')->hasColumn('tariffs', 'total_price')) {
                $table->dropColumn('total_price');
            }
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('tariffs', function (Blueprint $table): void {
            if (!Schema::connection('tenant')->hasColumn('tariffs', 'total_price')) {
                $table->unsignedInteger('total_price')->nullable();
            }
        });
    }
};
