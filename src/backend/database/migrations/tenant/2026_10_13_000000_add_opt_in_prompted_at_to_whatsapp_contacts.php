<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('whatsapp_contacts', static function (Blueprint $table) {
            $table->timestamp('opt_in_prompted_at')->nullable()->after('last_inbound_at');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('whatsapp_contacts', static function (Blueprint $table) {
            $table->dropColumn('opt_in_prompted_at');
        });
    }
};
