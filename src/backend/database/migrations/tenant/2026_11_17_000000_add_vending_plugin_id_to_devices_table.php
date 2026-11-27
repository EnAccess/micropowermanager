<?php

use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->unsignedInteger('vending_plugin_id')->nullable()->index()->after('device_serial');
        });

        // Mirrors the plugin service providers' mpmPluginId() and tokenIssuer(), hardcoded
        // so the backfill stays fixed however those providers change later.
        $pluginIdsByApiName = [
            'SparkMeterApi' => 1,
            'SteamaMeterApi' => 2,
            'CalinMeterApi' => 3,
            'CalinSmartMeterApi' => 4,
            'KelinMeterApi' => 5,
            'StronMeterApi' => 6,
            'MicroStarMeterApi' => 12,
            'SunKingSHSApi' => 13,
            'GomeLongMeterApi' => 14,
            'DalyBmsApi' => 16,
            'AngazaSHSApi' => 17,
            'ChintMeterApi' => 20,
            'DemoMeterManufacturerApi' => 21,
            'DemoShsManufacturerApi' => 22,
            'SparkShsApi' => 28,
        ];

        $deviceTables = [
            Meter::RELATION_NAME => 'meters',
            SolarHomeSystem::RELATION_NAME => 'solar_home_systems',
            EBike::RELATION_NAME => 'e_bikes',
        ];

        $tenant = DB::connection('tenant');

        foreach ($pluginIdsByApiName as $apiName => $pluginId) {
            foreach ($deviceTables as $deviceType => $deviceTable) {
                $tenant->table('devices')
                    ->where('device_type', $deviceType)
                    ->whereIn('device_id', $tenant->table($deviceTable)
                        ->select('id')
                        ->whereIn('manufacturer_id', $tenant->table('manufacturers')->select('id')->where('api_name', $apiName)))
                    ->update(['vending_plugin_id' => $pluginId]);
            }
        }
    }

    public function down(): void {
        Schema::connection('tenant')->table('devices', function (Blueprint $table) {
            $table->dropColumn('vending_plugin_id');
        });
    }
};
