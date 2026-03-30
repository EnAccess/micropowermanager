<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->table('tariffs', function (Blueprint $table) {
            $table->dropColumn('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->table('tariffs', function (Blueprint $table) {
            $table->integer('total_price')->unsigned()->nullable();
        });
    }
};
