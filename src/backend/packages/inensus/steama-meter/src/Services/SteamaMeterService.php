<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\Address\Address;
use App\Models\City;
use App\Models\ConnectionGroup;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Models\SteamaMeterType;
use Inensus\SteamaMeter\Models\SteamaTariff;
use Inensus\SteamaMeter\Models\SyncStatus;

class SteamaMeterService implements ISynchronizeService {
    private $rootUrl = '/meters';

    public function __construct(
        private SteamaMeter $stmMeter,
        private SteamaMeterApiClient $steamaApi,
        private ApiHelpers $apiHelpers,
        private Meter $meter,
        private SteamaCustomer $customer,
        private Manufacturer $manufacturer,
        private ConnectionGroup $connectionGroup,
        private MeterTariff $meterTariff,
        private City $city,
        private MeterType $meterType,
        private SteamaMeterType $stmMeterType,
        private SteamaTariff $tariff,
        private SteamaSyncSettingService $steamaSyncSettingService,
        private StemaSyncActionService $steamaSyncActionService,
    ) {}

    public function getMeters($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->stmMeter->newQuery()->with([
            'mpmMeter',
            'stmCustomer.site.mpmMiniGrid',
            'stmCustomer.mpmPerson',
        ])->paginate($perPage);
    }

    public function getMetersCount() {
        return count($this->stmMeter->newQuery()->get());
    }

    public function sync(): LengthAwarePaginator {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Meters');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
            })->each(function ($meter) {
                $createdMeter = $this->createRelatedMeter($meter);
                $this->stmMeter->newQuery()->create([
                    'meter_id' => $meter['id'],
                    'customer_id' => $meter['customer'],
                    'bit_harvester_id' => $meter['bit_harvester'],
                    'mpm_meter_id' => $createdMeter->id,
                    'hash' => $meter['hash'],
                ]);
            });
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::MODIFIED;
            })->each(function ($meter) {
                $relatedMeter = is_null($meter['relatedMeter']) ?
                    $this->createRelatedMeter($meter) : $this->updateRelatedMeter($meter, $meter['relatedMeter']);
                $meter['registeredStmMeter']->update([
                    'meter_id' => $meter['id'],
                    'customer_id' => $meter['customer'],
                    'bit_harvester_id' => $meter['bit_harvester'],
                    'mpm_meter_id' => $relatedMeter->id,
                    'hash' => $meter['hash'],
                ]);
            });
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->stmMeter->newQuery()->with([
                'mpmMeter',
                'stmCustomer.site.mpmMiniGrid',
                'stmCustomer.mpmPerson',
            ])->paginate(config('steama.paginate'));
        } catch (\Exception $e) {
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Steama meters sync failed.', ['Error :' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncCheck($returnData = false) {
        try {
            $url = $this->rootUrl.'?page=1&page_size=100';
            $result = $this->steamaApi->get($url);
            $meters = $result['results'];
            while ($result['next']) {
                $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                $result = $this->steamaApi->get($url);
                foreach ($result['results'] as $meter) {
                    array_push($meters, $meter);
                }
            }
        } catch (SteamaApiResponseException $e) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new SteamaApiResponseException($e->getMessage());
        }
        $metersCollection = collect($meters)->filter(function ($meter) {
            return $meter['customer'] !== null;
        });
        $stmMeters = $this->stmMeter->newQuery()->get();
        $meters = $this->meter->newQuery()->get();
        $metersCollection->transform(function ($meter) use ($stmMeters, $meters) {
            $registeredStmMeter = $stmMeters->firstWhere('meter_id', $meter['id']);
            $relatedMeter = null;
            $meterHash = $this->steamaMeterHasher($meter);
            if ($registeredStmMeter) {
                $meter['syncStatus'] = $meterHash === $registeredStmMeter->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedMeter = $meters->where('id', $registeredStmMeter->mpm_meter_id)->first();
            } else {
                $meter['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $meter['hash'] = $meterHash;
            $meter['relatedMeter'] = $relatedMeter;
            $meter['registeredStmMeter'] = $registeredStmMeter;

            return $meter;
        });
        $meterSyncStatus = $metersCollection->whereNotIn('syncStatus', SyncStatus::SYNCED)->count();
        if ($meterSyncStatus) {
            return $returnData ? ['data' => $metersCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $metersCollection, 'result' => true] : ['result' => true];
    }

    public function createRelatedMeter($stmMeter) {
        try {
            DB::connection('tenant')->beginTransaction();
            $meterSerial = $stmMeter['reference'];
            $meter = $this->meter->newQuery()->where('serial_number', $meterSerial)->first();
            $stmCustomer = $this->customer->newQuery()->with('mpmPerson')->where(
                'customer_id',
                $stmMeter['customer']
            )->first();
            if ($meter === null) {
                $meter = new Meter();
                $geoLocation = new GeographicalInformation();
            } else {
                $geoLocation = $meter->device->person->addresses()->first()->geo()->first();
                if ($geoLocation === null) {
                    $geoLocation = new GeographicalInformation();
                }
            }
            $meter->serial_number = $meterSerial;
            $manufacturer = $this->manufacturer->newQuery()->where('name', 'Steama Meters')->firstOrFail();
            $meter->manufacturer()->associate($manufacturer);
            $meter->updated_at = date('Y-m-d h:i:s');
            $meter->meterType()->associate($this->getMeterType($stmMeter));
            $meter->save();
            if ($stmCustomer) {
                if ($stmMeter['latitude'] !== null && $stmMeter['longitude'] !== null) {
                    $points = $stmMeter['latitude'].','.$stmMeter['longitude'];
                } else {
                    $points = explode(',', config('steama.geoLocation'));
                    $latitude = strval(doubleval($points[0]) - (mt_rand(10, 1000) / 10000));
                    $longitude = strval(doubleval($points[1]) - (mt_rand(10, 1000) / 10000));
                    $points = $latitude.','.$longitude;
                }

                $geoLocation->points = $points;

                $connectionType = $stmCustomer->userType->mpmConnectionType;
                $connectionGroup = $this->connectionGroup->newQuery()->first();
                if (!$connectionGroup) {
                    $connectionGroup = $this->connectionGroup->newQuery()->create([
                        'name' => 'default',
                    ]);
                }
                $meter->connection_type_id = $connectionType->id;
                $meter->connection_group_id = $connectionGroup->id;

                $tariff = $this->tariff->newQuery()->with('mpmTariff')->first();
                $meter->tariff()->associate($tariff->mpmTariff);
                $meter->save();
                $stmCustomerAddress = $stmCustomer->mpmPerson()->newQuery()->with('addresses.city')
                    ->whereHas('addresses', function ($q) {
                        return $q->where('is_primary', 1);
                    })->first();
                $cityName = $stmCustomerAddress->addresses[0]->city->name;

                $steamaCity = $this->city->newQuery()->with('miniGrid')->where('name', $cityName)->first();
                $address = new Address();
                $address = $address->newQuery()->create([
                    'city_id' => request()->input('city_id') ?? $steamaCity->id,
                ]);
                $address->owner()->associate($meter);
                $address->geo()->save($meter?->geo()->first());
                $address->save();
            }
            DB::connection('tenant')->commit();

            return $meter;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while synchronizing steama meters', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateRelatedMeter($stmMeter, $meter) {
        $meterSerial = $stmMeter['reference'];
        $meter->serial_number = $meterSerial;
        $meter->meterType()->associate($this->getMeterType($stmMeter));
        $meter->update();
        $stmCustomer = $this->customer->newQuery()->with('mpmPerson')->where(
            'customer_id',
            $stmMeter['customer']
        )->first();
        if ($stmCustomer) {
            $points = $stmMeter['latitude'] === null ?
                config('steama.geoLocation') : $stmMeter['latitude'].','.$stmMeter['longitude'];
            $meter->device->person->addresses()->first()->geo->geo()->update([
                'points' => $points,
            ]);
            $meter->save();
        }

        return $meter;
    }

    public function getMeterType($stmMeter) {
        $version = $stmMeter['version'];
        $usageSpikeThreshold = $stmMeter['usage_spike_threshold'];
        $stmMeterType = $this->stmMeterType->newQuery()->with('mpmMeterType')->where(
            'version',
            $version
        )->where('usage_spike_threshold', $usageSpikeThreshold)->first();
        if ($stmMeterType) {
            if ($stmMeterType->mpmMeterType) {
                return $stmMeterType->mpmMeterType;
            } else {
                return $this->meterType->newQuery()->create([
                    'online' => 1,
                    'phase' => 1,
                    'max_current' => $usageSpikeThreshold,
                ]);
            }
        } else {
            $meterType = $this->meterType->newQuery()->create([
                'online' => 1,
                'phase' => 1,
                'max_current' => $usageSpikeThreshold,
            ]);
            $this->stmMeterType->newQuery()->create([
                'version' => $version,
                'usage_spike_threshold' => $usageSpikeThreshold,
                'mpm_meter_type_id' => $meterType->id,
            ]);

            return $meterType;
        }
    }

    public function creteSteamaMeter($meterInfo, $stmCustomer) {
        $geographicalInformation = $meterInfo->address->geo;
        $points = explode(',', $geographicalInformation);
        $postParams = [
            'reference' => $meterInfo->meter->serial_number,
            'utility' => 1,
            'customer' => $stmCustomer->customer_id,
            'latitude' => intval($points[0]),
            'longitude' => intval($points[1]),
        ];
        $meter = $this->steamaApi->post($this->rootUrl.'/', $postParams);
        $stmMeterHash = $this->steamaMeterHasher($meter);

        return $this->stmMeter->newQuery()->create([
            'meter_id' => $meter['id'],
            'customer_id' => $stmCustomer->customer_id,
            'mpm_meter_id' => $meterInfo->meter_id,
            'hash' => $stmMeterHash,
        ]);
    }

    public function updateSteamaMeterInfo($stmMeter, $putParams) {
        $url = '/bitharvesters/'.$stmMeter->bit_harvester_id.$this->rootUrl.'/'.$stmMeter->meter_id.'/';
        $meter = $this->steamaApi->patch($url, $putParams);
        $stmMeterHash = $this->steamaMeterHasher($meter);
        $stmMeter->update([
            'hash' => $stmMeterHash,
        ]);

        return $stmMeter->fresh();
    }

    private function steamaMeterHasher($steamaMeter) {
        return $this->apiHelpers->makeHash([
            $steamaMeter['reference'],
            $steamaMeter['version'],
            $steamaMeter['utility'],
            $steamaMeter['customer'],
            $steamaMeter['power_limit'],
            $steamaMeter['latitude'],
            $steamaMeter['longitude'],
        ]);
    }
}
