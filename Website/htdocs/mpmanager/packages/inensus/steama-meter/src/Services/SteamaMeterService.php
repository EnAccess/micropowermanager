<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\Address\Address;
use App\Models\City;
use App\Models\ConnectionGroup;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
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

class SteamaMeterService implements ISynchronizeService
{
    private $stmMeter;
    private $steamaApi;
    private $apiHelpers;
    private $rootUrl = '/meters';
    private $meter;
    private $customer;
    private $manufacturer;
    private $connectionGroup;
    private $meterTariff;
    private $city;
    private $stmMeterType;
    private $meterType;
    private $meterParameter;
    private $tariff;
    private $steamaSyncSettingService;
    private $steamaSyncActionService;

    public function __construct(
        SteamaMeter $steamaMeterModel,
        SteamaMeterApiClient $steamaApi,
        ApiHelpers $apiHelpers,
        Meter $meter,
        SteamaCustomer $customer,
        Manufacturer $manufacturer,
        ConnectionGroup $connectionGroup,
        MeterTariff $meterTariff,
        City $city,
        MeterType $meterType,
        SteamaMeterType $stmMeterType,
        MeterParameter $meterParameter,
        SteamaTariff $tariff,
        SteamaSyncSettingService $steamaSyncSettingService,
        StemaSyncActionService $steamaSyncActionService
    ) {
        $this->stmMeter = $steamaMeterModel;
        $this->steamaApi = $steamaApi;
        $this->apiHelpers = $apiHelpers;
        $this->meter = $meter;
        $this->customer = $customer;
        $this->manufacturer = $manufacturer;
        $this->connectionGroup = $connectionGroup;
        $this->meterTariff = $meterTariff;
        $this->city = $city;
        $this->stmMeterType = $stmMeterType;
        $this->meterType = $meterType;
        $this->meterParameter = $meterParameter;
        $this->tariff = $tariff;
        $this->steamaSyncSettingService = $steamaSyncSettingService;
        $this->steamaSyncActionService = $steamaSyncActionService;
    }

    public function getMeters($request)
    {
        $perPage = $request->input('per_page') ?? 15;

        return $this->stmMeter->newQuery()->with([
            'mpmMeter',
            'stmCustomer.site.mpmMiniGrid',
            'stmCustomer.mpmPerson',
        ])->paginate($perPage);
    }

    public function getMetersCount()
    {
        return count($this->stmMeter->newQuery()->get());
    }

    public function sync()
    {
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
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false)
    {
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

    public function createRelatedMeter($stmMeter)
    {
        try {
            DB::connection('shard')->beginTransaction();
            $meterSerial = $stmMeter['reference'];
            $meter = $this->meter->newQuery()->where('serial_number', $meterSerial)->first();
            $stmCustomer = $this->customer->newQuery()->with('mpmPerson')->where(
                'customer_id',
                $stmMeter['customer']
            )->first();
            if ($meter === null) {
                $meter = new Meter();
                $meterParameter = new MeterParameter();
                $geoLocation = new GeographicalInformation();
            } else {
                $meterParameter = $this->meterParameter->newQuery()->where('meter_id', $meter->id)->first();
                $geoLocation = $meterParameter->geo()->first();
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
                $meterParameter->connection_type_id = $connectionType->id;
                $meterParameter->connection_group_id = $connectionGroup->id;
                $meterParameter->meter()->associate($meter);

                $meterParameter->owner()->associate($stmCustomer->mpmPerson);
                $tariff = $this->tariff->newQuery()->with('mpmTariff')->first();
                $meterParameter->tariff()->associate($tariff->mpmTariff);
                $meterParameter->save();
                $meterParameter->geo()->save($geoLocation);
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
                $address->owner()->associate($meterParameter);
                $address->geo()->associate($meterParameter->geo);
                $address->save();
            }
            DB::connection('shard')->commit();

            return $meter;
        } catch (\Exception $e) {
            DB::connection('shard')->rollBack();
            Log::critical('Error while synchronizing steama meters', ['message' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function updateRelatedMeter($stmMeter, $meter)
    {
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
            $meterParameter = $this->meterParameter->newQuery()->where('meter_id', $meter->id)->first();
            $meterParameter->owner()->associate($stmCustomer->mpmPerson());
            $meterParameter->geo()->update([
                'points' => $points,
            ]);
            $meterParameter->save();
        }

        return $meter;
    }

    public function getMeterType($stmMeter)
    {
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

    public function creteSteamaMeter($meterInfo, $stmCustomer)
    {
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

    public function updateSteamaMeterInfo($stmMeter, $putParams)
    {
        $url = '/bitharvesters/'.$stmMeter->bit_harvester_id.$this->rootUrl.'/'.$stmMeter->meter_id.'/';
        $meter = $this->steamaApi->patch($url, $putParams);
        $stmMeterHash = $this->steamaMeterHasher($meter);
        $stmMeter->update([
            'hash' => $stmMeterHash,
        ]);

        return $stmMeter->fresh();
    }

    private function steamaMeterHasher($steamaMeter)
    {
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
