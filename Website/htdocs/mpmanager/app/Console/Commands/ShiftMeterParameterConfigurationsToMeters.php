<?php

namespace App\Console\Commands;

use App\Services\MeterParameterService;
use App\Services\MeterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Inensus\BulkRegistration\Services\AddressService;
use MPM\Device\DeviceAddressService;
use MPM\Device\DeviceService;
use MPM\Device\MeterDeviceService;

class ShiftMeterParameterConfigurationsToMeters extends AbstractSharedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:meter-parameter-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shift meter_parameter values to meters, devices and addresses';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private MeterParameterService $meterParameterService,
        private MeterService $meterService,
        private MeterDeviceService $meterDeviceService,
        private DeviceService $deviceService,
        private DeviceAddressService $deviceAddressService,
        private AddressService $addressService

    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $meterParameters = $this->meterParameterService->getAll();
        $meterParameters->each(fn($meterParameter) => $this->setMeterDevices($meterParameter));
        return 0;
    }

    private function setMeterDevices($meterParameter)
    {
        try {
            DB::connection('shard')->beginTransaction();
            $this->info('Meter parameter values are being shifted to meters, devices and addresses.');
            $meter = $this->meterService->getById($meterParameter->meter_id);
            $meterData = [
                'connection_type_id' => $meterParameter->connection_type_id,
                'connection_group_id' => $meterParameter->connection_group_id,
                'tariff_id' => $meterParameter->tariff_id,
            ];
            $updatedMeter = $this->meterService->update($meter, $meterData);

            $device = $this->deviceService->make([
                'person_id' => $meterParameter->owner_id,
                'device_serial' => $meter->serial_number,
            ]);
            $this->meterDeviceService->setAssigned($device);
            $this->meterDeviceService->setAssignee($updatedMeter);
            $this->meterDeviceService->assign();
            $this->deviceService->save($device);

            $address = $meterParameter->address()->first();

            $this->deviceAddressService->setAssigned($address);
            $this->deviceAddressService->setAssignee($device);
            $this->deviceAddressService->assign();
            $this->deviceService->save($address);

            $this->meterParameterService->delete($meterParameter);
            $this->info('Meter parameter values are shifted to meters, devices and addresses.');
            DB::connection('shard')->commit();
        } catch (\Exception $e) {

            $message =  $e->getMessage();
            $this->info("Unexpected error occurred. Message: {$message}");
            DB::connection('shard')->rollBack();
        }

    }
}
