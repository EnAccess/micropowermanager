<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('sts_meters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('mpm_meter_id')->unique();
            $table->text('decoder_key');
            $table->string('base_date')->default('2014');
            $table->string('encryption_algorithm')->default('STA');
            $table->unsignedTinyInteger('key_expiry_number')->default(255);
            $table->dateTime('last_issued_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('sts_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sts_meter_id')->constrained('sts_meters');
            $table->unsignedInteger('transaction_id')->nullable()->unique();
            $table->unsignedInteger('token_id')->nullable()->unique();
            $table->foreign('token_id')->references('id')->on('tokens')->nullOnDelete();
            $table->string('token_type');
            $table->dateTime('issued_at');
            $table->unsignedTinyInteger('random_number');
            $table->decimal('kwh', 10, 1)->nullable();
            $table->string('token')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('sts_transactions');
        Schema::connection('tenant')->dropIfExists('sts_meters');
    }
};
