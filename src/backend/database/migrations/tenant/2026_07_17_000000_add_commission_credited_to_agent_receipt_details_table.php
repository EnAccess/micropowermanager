<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('agent_receipt_details', function (Blueprint $table) {
            $table->double('commission_credited')->nullable();
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('agent_receipt_details', function (Blueprint $table) {
            $table->dropColumn('commission_credited');
        });
    }
};
