<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('meters', function (Blueprint $table) {
            $table->string('vending_provider', 100)->nullable()->index()->after('manufacturer_id');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('meters', function (Blueprint $table) {
            $table->dropIndex(['vending_provider']);
            $table->dropColumn('vending_provider');
        });
    }
};
