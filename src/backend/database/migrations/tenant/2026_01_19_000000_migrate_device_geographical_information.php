<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Migrates GeographicalInformation from Address to Device.
     * Before: Device -> Address -> GeographicalInformation
     * After:  Device -> GeographicalInformation (direct)
     */
    public function up(): void {
        // Find all geographical_informations that belong to an address which belongs to a device
        $geoInfosToMigrate = DB::connection('tenant')
            ->table('geographical_informations as geo')
            ->join('addresses as addr', function ($join) {
                $join->on('geo.owner_id', '=', 'addr.id')
                    ->where('geo.owner_type', '=', 'address');
            })
            ->where('addr.owner_type', '=', 'device')
            ->select('geo.id as geo_id', 'addr.owner_id as device_id')
            ->get();

        foreach ($geoInfosToMigrate as $geoInfo) {
            DB::connection('tenant')
                ->table('geographical_informations')
                ->where('id', $geoInfo->geo_id)
                ->update([
                    'owner_type' => 'device',
                    'owner_id' => $geoInfo->device_id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Migrates GeographicalInformation from Device back to Address.
     */
    public function down(): void {
        // Find all geographical_informations that belong to a device
        $geoInfosToRevert = DB::connection('tenant')
            ->table('geographical_informations as geo')
            ->join('devices as dev', function ($join) {
                $join->on('geo.owner_id', '=', 'dev.id')
                    ->where('geo.owner_type', '=', 'device');
            })
            ->join('addresses as addr', function ($join) {
                $join->on('addr.owner_id', '=', 'dev.id')
                    ->where('addr.owner_type', '=', 'device');
            })
            ->select('geo.id as geo_id', 'addr.id as address_id')
            ->get();

        foreach ($geoInfosToRevert as $geoInfo) {
            DB::connection('tenant')
                ->table('geographical_informations')
                ->where('id', $geoInfo->geo_id)
                ->update([
                    'owner_type' => 'address',
                    'owner_id' => $geoInfo->address_id,
                ]);
        }
    }
};
