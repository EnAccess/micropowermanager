<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::connection('tenant')->hasTable('ecreee_token')) {
            Schema::connection('tenant')->create('ecreee_token', function (Blueprint $table) {
                $table->id();
                $table->text('token')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::connection('tenant')->dropIfExists('ecreee_token');
    }
};
