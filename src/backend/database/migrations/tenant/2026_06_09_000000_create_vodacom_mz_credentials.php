<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::connection('tenant')->create('vodacom_mz_credentials', function (Blueprint $table) {
            $table->id();
            $table->text('api_key')->nullable();
            $table->text('public_key')->nullable();
            $table->string('service_provider_code')->nullable();
            $table->boolean('live')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('vodacom_mz_credentials');
    }
};
