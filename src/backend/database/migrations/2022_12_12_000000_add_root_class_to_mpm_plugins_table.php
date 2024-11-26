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
     *
     * @return void
     */
    public function up() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->string('root_class')->nullable();
        });

        $mpm_plugins_mapping = [
            MpmPlugin::SPARK_METER => 'SparkMeter',
            MpmPlugin::STEAMACO_METER => 'SteamaMeter',
            MpmPlugin::CALIN_METER => 'CalinMeter',
            MpmPlugin::CALIN_SMART_METER => 'CalinSmartMeter',
            MpmPlugin::KELIN_METER => 'KelinMeter',
            MpmPlugin::STRON_METER => 'StronMeter',
            MpmPlugin::SWIFTA_PAYMENT_PROVIDER => 'SwiftaPaymentProvider',
            MpmPlugin::MESOMB_PAYMENT_PROVIDER => 'MesombPaymentProvider',
            MpmPlugin::BULK_REGISTRATION => 'BulkRegistration',
            MpmPlugin::VIBER_MESSAGING => 'ViberMessaging',
            MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER => 'WaveMoneyPaymentProvider',
            MpmPlugin::MICRO_STAR_METERS => 'MicroStarMeter',
        ];

        foreach ($mpm_plugins_mapping as $id => $value) {
            DB::table('mpm_plugins')->where('id', $id)->update([
                'root_class' => $value,
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->dropColumn('root_class');
        });
    }
};
