<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('whatsapp_credentials', function (Blueprint $table): void {
            $table->text('meta_app_secret')->nullable();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('whatsapp_credentials', function (Blueprint $table): void {
            $table->dropColumn('meta_app_secret');
        });
    }
};
