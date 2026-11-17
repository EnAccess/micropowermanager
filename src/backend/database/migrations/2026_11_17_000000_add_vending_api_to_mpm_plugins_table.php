<?php

use App\Models\MpmPlugin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->string('vending_api')->nullable();
        });

        $vendingApis = [
            MpmPlugin::SPARK_METER => 'SparkMeterApi',
            MpmPlugin::STEAMACO_METER => 'SteamaMeterApi',
            MpmPlugin::CALIN_METER => 'CalinMeterApi',
            MpmPlugin::CALIN_SMART_METER => 'CalinSmartMeterApi',
            MpmPlugin::KELIN_METER => 'KelinMeterApi',
            MpmPlugin::STRON_METER => 'StronMeterApi',
            MpmPlugin::MICRO_STAR_METERS => 'MicroStarMeterApi',
            MpmPlugin::SUN_KING_SHS => 'SunKingSHSApi',
            MpmPlugin::GOME_LONG_METERS => 'GomeLongMeterApi',
            MpmPlugin::DALY_BMS => 'DalyBmsApi',
            MpmPlugin::AGAZA_SHS => 'AngazaSHSApi',
            MpmPlugin::CHINT_METER => 'ChintMeterApi',
            MpmPlugin::DEMO_METER_MANUFACTURER => 'DemoMeterManufacturerApi',
            MpmPlugin::DEMO_SHS_MANUFACTURER => 'DemoShsManufacturerApi',
            MpmPlugin::SPARK_SHS => 'SparkShsApi',
        ];

        foreach ($vendingApis as $id => $vendingApi) {
            DB::table('mpm_plugins')->where('id', $id)->update(['vending_api' => $vendingApi]);
        }
    }

    public function down(): void {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->dropColumn('vending_api');
        });
    }
};
