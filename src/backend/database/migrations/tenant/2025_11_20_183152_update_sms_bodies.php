<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return  new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'OverdueAssetRateReminder')
            ->update(['reference' => 'OverdueApplianceRateReminder']);

        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'AssetRatePayment')
            ->update(['reference' => 'ApplianceRatePayment']);

        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'AssetRateReminder')
            ->update(['reference' => 'ApplianceRateReminder']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'OverdueApplianceRateReminder')
            ->update(['reference' => 'OverdueAssetRateReminder']);

        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'ApplianceRatePayment')
            ->update(['reference' => 'AssetRatePayment']);

        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', 'ApplianceRateReminder')
            ->update(['reference' => 'AssetRateReminder']);
    }
};
