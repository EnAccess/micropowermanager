<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->create('calin_v2_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('api_url')->nullable();
            $table->text('company')->nullable();
            $table->text('user_id')->nullable();
            $table->text('password')->nullable();
            $table->text('authorization_password')->nullable();
            $table->string('vend_by')->default('energy');
            $table->text('access_token')->nullable();
            $table->unsignedBigInteger('token_expires_in')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('calin_v2_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('calin_v2_transactions');
        Schema::connection('tenant')->dropIfExists('calin_v2_api_credentials');
    }
};
