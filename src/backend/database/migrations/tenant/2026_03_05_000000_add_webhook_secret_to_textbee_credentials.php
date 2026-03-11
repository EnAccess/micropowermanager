<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('textbee_credentials', static function (Blueprint $table) {
            $table->text('webhook_secret')->nullable()->after('device_id');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('textbee_credentials', static function (Blueprint $table) {
            $table->dropColumn('webhook_secret');
        });
    }
};
