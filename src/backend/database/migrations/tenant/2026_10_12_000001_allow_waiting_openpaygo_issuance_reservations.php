<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('openpaygo_issuance_reservations', function (Blueprint $table): void {
            $table->unsignedInteger('counter')->nullable()->change();
            $table->unsignedInteger('starting_code')->nullable()->change();
            $table->text('secret_key_ciphertext')->nullable()->change();
            $table->string('last_error')->nullable();
        });
    }

    public function down(): void {
        if (DB::connection('tenant')->table('openpaygo_issuance_reservations')->whereNull('counter')->exists()) {
            throw new RuntimeException('Waiting OpenPAYGO payments must be resolved before this migration can be rolled back.');
        }

        Schema::connection('tenant')->table('openpaygo_issuance_reservations', function (Blueprint $table): void {
            $table->unsignedInteger('counter')->nullable(false)->change();
            $table->unsignedInteger('starting_code')->nullable(false)->change();
            $table->text('secret_key_ciphertext')->nullable(false)->change();
            $table->dropColumn('last_error');
        });
    }
};
