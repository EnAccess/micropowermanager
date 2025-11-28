<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('protected_pages');

        if (Schema::hasColumn('companies', 'protected_page_password')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->dropColumn('protected_page_password');
            });
        }
    }

    public function down(): void {
        if (!Schema::hasTable('protected_pages')) {
            Schema::create('protected_pages', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('companies', 'protected_page_password')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->string('protected_page_password')->nullable();
            });
        }
    }
};
