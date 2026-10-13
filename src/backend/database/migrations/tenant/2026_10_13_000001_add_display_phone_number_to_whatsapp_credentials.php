<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
            $table->string('display_phone_number')->nullable()->after('business_account_id');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('whatsapp_credentials', static function (Blueprint $table) {
            $table->dropColumn('display_phone_number');
        });
    }
};
