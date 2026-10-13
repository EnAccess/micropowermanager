<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('openpaygo_issuance_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('transaction_id')->unique();
            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('active_device_id')->nullable()->unique()->constrained('devices')->cascadeOnDelete();
            $table->string('operation', 32);
            $table->string('generator_operation', 32);
            $table->unsignedInteger('generator_value')->nullable();
            $table->unsignedInteger('counter');
            $table->unsignedInteger('starting_code');
            $table->text('secret_key_ciphertext');
            $table->string('state', 32);
            $table->string('token')->nullable();
            $table->unsignedInteger('next_counter')->nullable();
            $table->timestamps();
            $table->index(['state', 'updated_at']);
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('openpaygo_issuance_reservations');
    }
};
