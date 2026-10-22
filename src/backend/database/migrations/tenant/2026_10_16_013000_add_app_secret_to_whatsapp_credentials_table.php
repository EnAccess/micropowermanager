<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
            $table->text('app_secret')->nullable()->after('webhook_secret');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
            $table->dropColumn('app_secret');
        });
    }
};
