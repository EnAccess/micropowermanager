<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('steama_transactions', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('steama_transactions', function (Blueprint $table) {
            $table->decimal('amount')->change();
        });
    }
};
