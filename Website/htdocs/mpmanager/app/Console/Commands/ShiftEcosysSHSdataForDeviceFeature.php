<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetPerson;
use App\Models\ConnectionGroup;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use Illuminate\Support\Facades\DB;


class ShiftEcosysSHSdataForDeviceFeature extends AbstractSharedCommand
{
    //--company-id=36
    protected $signature = 'ecosys:shift-data {--company-id=}';
    protected $description = 'custom data shifting command for ecosys';

    private $assetTypePricePeer = [
        6450 => 2,
        6000 => 3
    ];

    private $connectionAssetPeer = [
        2 => 1,
        3 => 2
    ];

    public function __construct(
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Command started');
        $this->info('command running w ' . $this->option('company-id'));
        try {
            $devices = Device::query()->with('device')->get();
            $devices->map(function ($q) {
                $meter = $q->device;
                if ($meter->connection_group_id === 2 || $meter->connection_group_id === 3) {
                    $shsData = [
                        'asset_id' => $this->connectionAssetPeer[$meter->connection_group_id],
                        'serial_number' => $q->device_serial,
                        'manufacturer_id' => 8,
                        'created_at' => $q->device->created_at,
                        'updated_at' => $q->device->updated_at,
                    ];
                    $shs = SolarHomeSystem::query()->create($shsData);
                    $q->device_type = SolarHomeSystem::RELATION_NAME;
                    $q->device_id = $shs->id;
                    $q->save();
                    $assetPerson = AssetPerson::query()
                        ->where('person_id', $q->person_id)
                        ->where('device_serial', null)
                        ->orWhere('device_serial', '')->first();
                    if ($assetPerson) {
                        $assetPerson->device_serial = $q->device_serial;
                        $assetPerson->save();
                    }
                }
            });
            DB::connection('shard')->table('meters')->truncate();
            DB::connection('shard')->table('meter_tariffs')->truncate();

            $connectionGroups = ConnectionGroup::query()->whereIn('id',[2,3])->get()->map(function($q){
                $q->delete();
                $q->save();
            });

        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->info("Unexpected error occurred. Message: {$message}");

        }
    }
}