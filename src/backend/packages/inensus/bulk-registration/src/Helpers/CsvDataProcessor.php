<?php

namespace Inensus\BulkRegistration\Helpers;

use App\Events\AccessRatePaymentInitialize;
use App\Models\Address\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvDataProcessor {
    public const CONNECTION_GROUP = 1;
    private $geographicalLocationFinder;
    private $reflections;
    private $recentlyCreatedRecords;

    public function __construct(GeographicalLocationFinder $geographicalLocationFinder) {
        $this->geographicalLocationFinder = $geographicalLocationFinder;
        $this->reflections = config('bulk-registration.reflections');
        $this->recentlyCreatedRecords = [
            'cluster' => 0,
            'mini_grid' => 0,
            'village' => 0,
            'customer' => 0,
            'tariff' => 0,
            'connection_type' => 0,
            'connection_group' => 0,
            'meter' => 0,
        ];
    }

    public function processParsedCsvData($csvData) {
        Collect($csvData)->each(function ($row) {
            try {
                DB::connection('tenant')->beginTransaction();
                $person = $this->createRecordFromCsv($row, $this->reflections['PersonService']);
                $row['person_id'] = $person->id;
                $this->checkRecordWasRecentlyCreated($person, 'customer');

                $cluster = $this->createRecordFromCsv($row, $this->reflections['ClusterService']);
                $row['cluster_id'] = $cluster->id;
                $this->checkRecordWasRecentlyCreated($cluster, 'cluster');

                $miniGrid = $this->createRecordFromCsv($row, $this->reflections['MiniGridService']);
                $row['mini_grid_id'] = $miniGrid->id;
                $this->checkRecordWasRecentlyCreated($miniGrid, 'mini_grid');

                $city = $this->createRecordFromCsv($row, $this->reflections['CityService']);
                $row['city_id'] = $city->id;
                $this->checkRecordWasRecentlyCreated($city, 'village');

                $this->createRecordFromCsv($row, $this->reflections['AddressService']);

                $tariff = $this->createRecordFromCsv($row, $this->reflections['TariffService']);
                $row['tariff_id'] = $tariff->id;
                $this->checkRecordWasRecentlyCreated($tariff, 'tariff');

                $connectionType = $this->createRecordFromCsv($row, $this->reflections['ConnectionTypeService']);
                $row['connection_type_id'] = $connectionType->id;
                $this->checkRecordWasRecentlyCreated($connectionType, 'connection_type');

                $row['connection_group_id'] = self::CONNECTION_GROUP;

                $manufacturer = $this->createRecordFromCsv($row, $this->reflections['ManufacturerService']);

                if ($manufacturer) {
                    $row['manufacturer_id'] = $manufacturer->id;
                    $meter = $this->createRecordFromCsv($row, $this->reflections['MeterService']);
                    if ($meter) {
                        $row['meter_id'] = $meter->id;
                        $this->checkRecordWasRecentlyCreated($meter, 'meter');
                        // initializes a new Access Rate Payment for the next Period
                        event(new AccessRatePaymentInitialize($meter));
                        $geographicalInformationService =
                            app()->make($this->reflections['GeographicalInformationService']);
                        $geographicalInformationService->resolveCsvDataFromComingRow($row, $meter);

                        $address = new Address();
                        $address = $address->newQuery()->create([
                            'city_id' => $city->id,
                        ]);
                        $address->owner()->associate($meter);

                        $address->geo()->associate($meter->device->person->addresses()->first()->geo());
                        $address->save();
                    }
                }
                DB::connection('tenant')->commit();
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
                DB::connection('tenant')->rollBack();
                throw $exception;
            }
        });

        return $this->recentlyCreatedRecords;
    }

    private function createRecordFromCsv($row, $serviceName) {
        $service = app()->make($serviceName);

        return $service->resolveCsvDataFromComingRow($row);
    }

    private function checkRecordWasRecentlyCreated($record, $type) {
        if ($record->wasRecentlyCreated) {
            ++$this->recentlyCreatedRecords[$type];
        }
    }
}
