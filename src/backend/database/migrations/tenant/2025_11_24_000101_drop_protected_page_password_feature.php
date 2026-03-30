<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::connection('tenant')->hasColumn('main_settings', 'protected_page_password')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table): void {
                $table->dropColumn('protected_page_password');
            });
        }

        Schema::connection('tenant')->dropIfExists('protected_page_password_reset_tokens');
    }

    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('protected_page_password_reset_tokens')) {
            Schema::connection('tenant')->create('protected_page_password_reset_tokens', function (Blueprint $table): void {
                $table->id();
                $table->string('email')->index();
                $table->string('token')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasColumn('main_settings', 'protected_page_password')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table): void {
                $table->longText('protected_page_password')->nullable();
            });
        }
    }
};
