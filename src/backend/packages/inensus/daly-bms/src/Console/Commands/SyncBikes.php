<?php

namespace Inensus\DalyBms\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\DalyBms\Modules\Api\DalyBmsApi;
use MPM\Device\DeviceAddressService;
use MPM\EBike\EBikeService;

class SyncBikes extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 16;
    public const MANUFACTURER_NAME = 'DalyBms';

    protected $signature = 'daly-bms:sync-bikes';
    protected $description = 'Sync bikes from Daly BMS.';

    public function __construct(
        private EBikeService $eBikeService,
        private DalyBmsApi $dalyBmsApi,
        private DeviceAddressService $deviceAddressService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Daly BMS Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('sync-bikes command started at '.$startedAt);

        try {
            $serialNumbers =
                $this->eBikeService->getAll()->where('manufacturer.name', self::MANUFACTURER_NAME)->pluck('serial_number')
                    ->toArray();
            $devices = $this->dalyBmsApi->getDevices($serialNumbers);
            foreach ($devices as $device) {
                $this->updateBike($device);
            }
        } catch (\Exception $e) {
            $this->warn('sync-bikes command is failed. message => '.$e->getMessage());
        }

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }

    private function updateBike($deviceData) {
        $updatingData = [
            'receive_time' => $deviceData['ReceiveTime'],
            'lat' => strval($deviceData['Lat']),
            'lng' => strval($deviceData['Lng']),
            'speed' => $deviceData['Speed'],
            'mileage' => $deviceData['Mileage'],
            'status' => $deviceData['Status'],
            'soh' => $deviceData['SOH'],
            'battery_level' => $deviceData['BatteryLevel'],
            'battery_voltage' => $deviceData['BatteryVoltage'],
        ];
        $eBike = $this->eBikeService->getBySerialNumber($deviceData['Code']);
        $this->eBikeService->update(
            $eBike,
            $updatingData
        );

        $address = $this->deviceAddressService->getAddressByDevice($eBike->device);
        $geoData = [
            'points' => $updatingData['lat'].','.$updatingData['lng'],
        ];

        $address->geo->points = $geoData['points'];
        $address->geo->save();
    }
}
