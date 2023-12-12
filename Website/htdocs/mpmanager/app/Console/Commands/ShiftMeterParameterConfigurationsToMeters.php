<?php

namespace App\Console\Commands;

use App\Models\Meter\MeterToken;
use App\Services\CityGeographicalInformationService;
use App\Services\CityService;
use App\Services\ManufacturerService;
use App\Services\MenuItemsService;
use App\Services\MiniGridService;
use App\Services\TokenService;
use MPM\Meter\MeterDeviceService;
use App\Services\AddressesService;
use App\Services\GeographicalInformationService;
use App\Services\MeterParameterService;
use App\Services\MeterService;
use Illuminate\Support\Facades\DB;
use MPM\Device\DeviceAddressService;
use MPM\Device\DeviceService;

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
        private GeographicalInformationService $geographicalInformationService,
        private AddressesService $addressService,
        private CityService $cityService,
        private MiniGridService $miniGridService,
        private CityGeographicalInformationService $cityGeographicalInformationService,
        private MenuItemsService $menuItemsService,
        private ManufacturerService $manufacturerService,
        private TokenService $tokenService,
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
        try {
            DB::connection('shard')->beginTransaction();
            $cities = $this->cityService->getAll();
            $cities->each(fn($city) => $this->createGeoRecordForCity($city));
            $this->info('Geo records are created for cities.');
            $this->addSolarHomeSystemsToNavBar();
            $this->info('Solar Home Systems are added to the navigation bar.');
            $this->updateManufacturerTypeIfSunKingPluginIsInstalled();
            $this->info('Manufacturer type is updated if SunKing plugin is installed.');
            $this->moveMeterTokensToTokens();
            $this->info('Meter tokens are moved to tokens.');
            $meterParameters = $this->meterParameterService->getAll();
            $this->info('Meter parameter values are being shifted to meters, devices and addresses.');
            $meterParameters->each(fn($meterParameter) => $this->setMeterDevices($meterParameter));
            DB::connection('shard')->commit();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->info("Unexpected error occurred. Message: {$message}");
            DB::connection('shard')->rollBack();
        }

        return 0;
    }

    private function setMeterDevices($meterParameter)
    {
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
        $this->addressService->save($address);
        $this->geographicalInformationService->changeOwnerWithAddress($meterParameter, $address->id);
        $this->meterParameterService->delete($meterParameter);
        $this->info('Meter parameter values are shifted to meters, devices and addresses.');
    }

    private function createGeoRecordForCity($city)
    {
        if ($city->location == null) {
            $miniGridLocation = $this->miniGridService->getByIdWithLocation($city->mini_grid_id)->location;
            $cityGeo = $this->geographicalInformationService->make([
                'points' => $miniGridLocation->points
            ]);
            $this->cityGeographicalInformationService->setAssigned($cityGeo);
            $this->cityGeographicalInformationService->setAssignee($city);
            $this->cityGeographicalInformationService->assign();
            $this->geographicalInformationService->save($cityGeo);
        }
        return $city;
    }

    private function addSolarHomeSystemsToNavBar()
    {
        $shsMenuItem = 'Solar Home Systems';
        $menuItem = $this->menuItemsService->getByName($shsMenuItem);

        if (!$menuItem) {
            $this->menuItemsService->create([
                'name' => 'Solar Home Systems',
                'url_slug' => '/solar-home-systems/page/1',
                'md_icon' => 'solar_power',
                'menu_order' => 0,
            ]);
        }
    }

    private function updateManufacturerTypeIfSunKingPluginIsInstalled()
    {
        $manufacturer = $this->manufacturerService->getByName('SunKing SHS');
        if ($manufacturer) {
            $manufacturer->type = 'shs';
            $manufacturer->save();
        }
    }

    private function moveMeterTokensToTokens()
    {
        $meterTokens = MeterToken::query()->get();
        $meterTokens->each(function ($meterToken) {
            $this->tokenService->create([
                'transaction_id' => $meterToken->transaction_id,
                'token' => $meterToken->token,
                'load' => $meterToken->energy,
            ]);
            $meterToken->delete();
        });
    }
}
