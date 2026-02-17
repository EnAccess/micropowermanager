<?php

namespace App\Plugins\BulkRegistration\Helpers;

use App\Events\AccessRatePaymentInitialize;
use App\Models\Address\Address;
use App\Models\City;
use App\Models\Cluster;
use App\Models\ConnectionType;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\Tariff;
use App\Plugins\BulkRegistration\Services\GeographicalInformationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvDataProcessor {
    public const CONNECTION_GROUP = 1;
    /**
     * @var array<string, class-string>
     */
    private array $reflections;
    /**
     * @var array<string, int>
     */
    private array $recentlyCreatedRecords = [
        'cluster' => 0,
        'mini_grid' => 0,
        'village' => 0,
        'customer' => 0,
        'tariff' => 0,
        'connection_type' => 0,
        'connection_group' => 0,
        'meter' => 0,
    ];

    public function __construct() {
        $this->reflections = config('bulk-registration.reflections');
    }

    /**
     * @param array<string|int, mixed> $csvData
     *
     * @return array<string, int>
     */
    public function processParsedCsvData(array $csvData): array {
        Collect($csvData)->each(function (array $row) {
            try {
                DB::connection('tenant')->beginTransaction();
                /** @var Person */
                $person = $this->createRecordFromCsv($row, $this->reflections['PersonService']);
                $row['person_id'] = $person->id;
                $this->checkRecordWasRecentlyCreated($person, 'customer');

                /** @var Cluster */
                $cluster = $this->createRecordFromCsv($row, $this->reflections['ClusterService']);
                $row['cluster_id'] = $cluster->id;
                $this->checkRecordWasRecentlyCreated($cluster, 'cluster');

                /** @var MiniGrid */
                $miniGrid = $this->createRecordFromCsv($row, $this->reflections['MiniGridService']);
                $row['mini_grid_id'] = $miniGrid->id;
                $this->checkRecordWasRecentlyCreated($miniGrid, 'mini_grid');

                /** @var City */
                $city = $this->createRecordFromCsv($row, $this->reflections['CityService']);
                $row['city_id'] = $city->id;
                $this->checkRecordWasRecentlyCreated($city, 'village');

                $this->createRecordFromCsv($row, $this->reflections['AddressService']);

                /** @var Tariff */
                $tariff = $this->createRecordFromCsv($row, $this->reflections['TariffService']);
                $row['tariff_id'] = $tariff->id;
                $this->checkRecordWasRecentlyCreated($tariff, 'tariff');

                /** @var ConnectionType */
                $connectionType = $this->createRecordFromCsv($row, $this->reflections['ConnectionTypeService']);
                $row['connection_type_id'] = $connectionType->id;
                $this->checkRecordWasRecentlyCreated($connectionType, 'connection_type');

                $row['connection_group_id'] = self::CONNECTION_GROUP;

                /** @var ?Manufacturer */
                $manufacturer = $this->createRecordFromCsv($row, $this->reflections['ManufacturerService']);

                if ($manufacturer) {
                    $row['manufacturer_id'] = $manufacturer->id;
                    /** @var ?Meter */
                    $meter = $this->createRecordFromCsv($row, $this->reflections['MeterService']);
                    if ($meter) {
                        $row['meter_id'] = $meter->id;
                        $this->checkRecordWasRecentlyCreated($meter, 'meter');
                        // initializes a new Access Rate Payment for the next Period
                        event(new AccessRatePaymentInitialize($meter));
                        /** @var GeographicalInformationService */
                        $geographicalInformationService =
                            app()->make($this->reflections['GeographicalInformationService']);
                        $geographicalInformationService->resolveCsvDataFromComingRow($row, $meter);

                        $address = new Address();
                        $address = $address->newQuery()->make([
                            'city_id' => $city->id,
                        ]);
                        $address->owner()->associate($meter);
                        // See: https://github.com/EnAccess/micropowermanager/issues/1004
                        // FIXME: We are not creating Devices in the Csv processing
                        // $address->geo()->save($meter->device->address->geo()->first());
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

    /**
     * @param class-string         $serviceName
     * @param array<string, mixed> $row
     *
     * @return object|void|bool
     */
    private function createRecordFromCsv(array $row, string $serviceName) {
        $service = app()->make($serviceName);

        return $service->resolveCsvDataFromComingRow($row);
    }

    private function checkRecordWasRecentlyCreated(Model $record, string $type): void {
        if ($record->wasRecentlyCreated) {
            ++$this->recentlyCreatedRecords[$type];
        }
    }
}
