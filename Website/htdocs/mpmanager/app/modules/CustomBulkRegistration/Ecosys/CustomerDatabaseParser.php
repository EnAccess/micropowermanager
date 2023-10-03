<?php

namespace MPM\CustomBulkRegistration\Ecosys;

use App\Models\Address\Address;
use App\Models\Cluster;
use App\Models\ConnectionType;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\MeterTariff;
use App\Models\MiniGrid;
use App\Models\SubConnectionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MPM\CustomBulkRegistration\Ecosys\Services\AddressService;
use MPM\CustomBulkRegistration\Ecosys\Services\AppliancePersonService;
use MPM\CustomBulkRegistration\Ecosys\Services\ApplianceService;
use MPM\CustomBulkRegistration\Ecosys\Services\CityService;
use MPM\CustomBulkRegistration\Ecosys\Services\ConnectionGroupService;
use MPM\CustomBulkRegistration\Ecosys\Services\GeographicalInformationService;
use MPM\CustomBulkRegistration\Ecosys\Services\MeterParameterService;
use MPM\CustomBulkRegistration\Ecosys\Services\MeterService;
use MPM\CustomBulkRegistration\Ecosys\Services\PersonService;
use ParseCsv\Csv;

class CustomerDatabaseParser
{
    private $path;
    private $recentlyCreatedRecords;

    public function __construct(private Csv $csv)
    {
        $this->path = __DIR__ . '/CustomerDb/customer_db.csv';
        $this->recentlyCreatedRecords = [
            'customer' => 0,
            'connection_group' => 0,
            'meter' => 0,
            'asset' => 0,
            'appliance_person' => 0,
        ];
    }


    private function parseCsvFromFilePath()
    {
        $this->csv->auto($this->path);
        return $this->csv->data;
    }

    public function insertFromCsv()
    {
        $parsedCsvData = $this->parseCsvFromFilePath();
        $cluster = Cluster::query()->first();
        $miniGrid = MiniGrid::query()->first();
        $connectionType = ConnectionType::query()->where('name', 'Residential')->first();
        $manufacturer = Manufacturer::query()->where('name', 'SunKing SHS')->first();
        $tariff = MeterTariff::query()->firstOrCreate([
            'name'=>'SunKing SHS Initial Tariff',
        ],[
            'name' => 'SunKing SHS Initial Tariff',
            'price' => 0,
            'total_price' => 0,
            'currency' => MainSettings::query()->first() ? MainSettings::query()->first()->currency : 'MTn',
            'factor' => 2,
        ]);

        Collect($parsedCsvData)->each(function ($row) use (
            $miniGrid,
            $cluster,
            $connectionType,
            $manufacturer,
            $tariff
        ) {

            try{
                DB::connection('shard')->beginTransaction();
                $row['cluster_id'] = $cluster->id;
                $row['mini_grid_id'] = $miniGrid->id;
                $row['tariff_id'] = $tariff->id;

                $personResult = $this->createRecordFromCsv($row, PersonService::class);
                $person = $personResult['person'];
                $isExistingPerson = $personResult['existing'];
                $row['person_id'] = $person->id;
                $this->checkRecordWasRecentlyCreated($person, 'customer');

                $city = $this->createRecordFromCsv($row, CityService::class);
                $row['city_id'] = $city->id;


                if(!$isExistingPerson){
                    $this->createRecordFromCsv($row, AddressService::class);
                }

                $connectionGroup = $this->createRecordFromCsv($row, ConnectionGroupService::class);
                $row['connection_group_id'] = $connectionGroup->id;
                $this->checkRecordWasRecentlyCreated($connectionGroup, 'connection_group');
                $row['connection_type_id'] = $connectionType->id;
                $row['manufacturer_id'] = $manufacturer->id;

                $meter = $this->createRecordFromCsv($row, MeterService::class);
                $row['meter_id'] = $meter->id;
                $row['serial_number'] = $meter->serial_number;
                $this->checkRecordWasRecentlyCreated($meter, 'meter');

                $meterParameter = $this->createRecordFromCsv($row, MeterParameterService::class);
                event('accessRatePayment.initialize', $meterParameter);
                $row['meter_parameter_id'] = $meterParameter->id;

                $this->createRecordFromCsv($row, GeographicalInformationService::class);
                $meterAddress = new Address();
                $address = $meterAddress->newQuery()->create([
                    'city_id' => $city->id,
                ]);
                $address->owner()->associate($meterParameter);
                $address->geo()->associate($meterParameter->geo);
                $address->save();

                $appliance = $this->createRecordFromCsv($row, ApplianceService::class);
                $row['asset_id'] = $appliance->id;
                $row['appliance_name']=$appliance->name;
                $this->checkRecordWasRecentlyCreated($appliance, 'asset');

                $appliancePerson = $this->createRecordFromCsv($row, AppliancePersonService::class);
                $this->checkRecordWasRecentlyCreated($appliancePerson, 'appliance_person');
                DB::connection('shard')->commit();
            }catch (\Exception $exception) {
                Log::error($exception->getMessage());
                DB::connection('shard')->rollBack();
                throw $exception;
            }
        });

        return $this->recentlyCreatedRecords;
    }

    private function createRecordFromCsv($row, $service)
    {
        $service = app()->make($service);
        return $service->resolveCsvDataFromComingRow($row);
    }

    private function checkRecordWasRecentlyCreated($record, $type)
    {
        if ($record->wasRecentlyCreated) {
            $this->recentlyCreatedRecords[$type]++;
        }
    }
}