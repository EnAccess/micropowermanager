<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        DB::connection('micro_power_manager')
            ->table('mpm_plugins')
            ->where('id', MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER)
            ->update([
                'name' => 'VodacomMzPaymentProvider',
                'description' => 'This plugin enables payments via M-Pesa (Vodacom Mozambique).',
                'tail_tag' => 'Vodacom Mz Payment Provider',
                'installation_command' => 'vodacom-mz:install',
                'root_class' => 'VodacomMzPaymentProvider',
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        DB::connection('micro_power_manager')
            ->table('mpm_plugins')
            ->where('id', MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER)
            ->update([
                'name' => 'VodacomMobileMoney',
                'description' => 'This plugin developed to payment via vodacom mobile money provider',
                'tail_tag' => 'Vodacom Mobile Money',
                'installation_command' => 'vodacom-mobile-money:install',
                'root_class' => 'VodacomMobileMoney',
                'updated_at' => Carbon::now(),
            ]);
    }
};
