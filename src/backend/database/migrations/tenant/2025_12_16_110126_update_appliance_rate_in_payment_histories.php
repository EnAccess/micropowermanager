<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::connection('tenant')->table('payment_histories')
            ->where('paid_for_type', 'asset_rate')
            ->update(['paid_for_type' => 'appliance_rate']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::connection('tenant')->table('payment_histories')
            ->where('paid_for_type', 'appliance_rate')
            ->update(['paid_for_type' => 'asset_rate']);
    }
};
