<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('appliance_people', function (Blueprint $table) {
            $table->string('payment_type', 20)->default('installment')->after('device_serial');
            $table->integer('minimum_payable_amount')->nullable()->after('payment_type');
            $table->integer('price_per_day')->nullable()->after('minimum_payable_amount');
        });
    }

    public function down(): void {
        Schema::connection('tenant')->table('appliance_people', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'minimum_payable_amount', 'price_per_day']);
        });
    }
};
