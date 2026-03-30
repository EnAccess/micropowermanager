<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        DB::connection('tenant')->table('tokens')
            ->whereNull('token_type')
            ->update(['token_type' => 'energy']);

        Schema::connection('tenant')->table('tokens', function (Blueprint $table) {
            $table->string('token_type')->nullable(false)->default('energy')->change();
            $table->float('token_amount')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('tokens', function (Blueprint $table) {
            $table->string('token_type')->nullable()->default(null)->change();
            $table->float('token_amount')->nullable(false)->change();
        });
    }
};
