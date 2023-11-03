<?php

namespace App\Services;

use App\Http\Requests\MeterRequest;
use App\Models\City;
use App\Models\Meter\Meter;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

use function count;

class MeterService implements IBaseService
{
    public function __construct(private Meter $meter)
    {
    }

    public function getBySerialNumber($serialNumber)
    {
        return $this->meter->newQuery()->with([
                'meterParameter.tariff',
                'meterParameter.owner',
                'meterType',
                'meterParameter.connectionType',
                'meterParameter.connectionGroup',
                'manufacturer'
            ])->where('serial_number', $serialNumber)->first();
    }

    public function search($term, $paginate): LengthAwarePaginator
    {
        return $this->meter->newQuery()->with(['meterType', 'meterParameter.tariff'])
            ->whereHas(
                'meterParameter.tariff',
                function ($q) use ($term) {
                    return $q->where('name', 'LIKE', '%' . $term . '%');
                }
            )
            ->orWhere(
                'serial_number',
                'LIKE',
                '%' . $term . '%'
            )->paginate($paginate);
    }

    public function getMeterWithAllRelations(int $meterId)
    {
        return $this->meter->newQuery()->with([
            'meterParameter.tariff',
            'meterParameter.geo',
            'meterType'])->find($meterId);
    }

    public function getUsedMetersGeoWithAccessRatePayments(): Collection|array
    {
        return $this->meter->newQuery()->with('meterParameter.address.geo', 'accessRatePayment')->where(
            'in_use',
            1
        )->get();
    }

    public function getUsedMetersGeoWithAccessRatePaymentsInCities($cities): Collection|array
    {
        return $this->meter->newQuery()->with('meterParameter.address.geo', 'accessRatePayment')
            ->whereHas(
                'meterParameter',
                function ($q) use ($cities) {
                    $q->whereHas(
                        'address',
                        function ($q) use ($cities) {
                            $q->whereIn('city_id', $cities);
                        }
                    );
                }
            )
            ->where('in_use', 1)->get();
    }

    public function getMeterCountInCluster($clusterId)
    {
        return $this->meter->newQuery()->whereHas(
            'meterParameter',
            function ($q) use ($clusterId) {
                $q->whereHas(
                    'address',
                    function ($q) use ($clusterId) {
                        $q->whereHas(
                            'city',
                            function ($q) use ($clusterId) {
                                $q->where('cluster_id', $clusterId);
                            }
                        );
                    }
                );
            }
        )->count();
    }

    public function getMeterCountInMiniGrid($miniGridId)
    {
        return $this->meter->newQuery()->whereHas(
            'meterParameter',
            function ($q) use ($miniGridId) {
                $q->whereHas(
                    'address',
                    function ($q) use ($miniGridId) {
                        $q->whereHas(
                            'city',
                            function ($q) use ($miniGridId) {
                                $q->where('mini_grid_id', $miniGridId);
                            }
                        );
                    }
                );
            }
        )->count();
    }

    public function getMeterCountInCity($cityId)
    {
        return $this->meter->newQuery()->whereHas(
            'meterParameter',
            function ($q) use ($cityId) {
                $q->whereHas(
                    'address',
                    function ($q) use ($cityId) {
                        $q->where('city_id', $cityId);
                    }
                );
            }
        )->count();
    }

    public function meterTransactions(City $city): City
    {
        $cityId = $city->id;
        $meters = $this->meter->newQuery()->whereHas(
            'meterParameter',
            function ($q) use ($cityId) {
                $q->whereHas(
                    'address',
                    function ($q) use ($cityId) {
                        $q->where('city_id', $cityId);
                    }
                );
            }
        )->count();

        $city['metersCount'] = $meters;
        return $city;
    }

    public function getMetersInCity(City $city): City
    {
        $cityId = $city->id;
        $meters = $this->meter->newQuery()->whereHas(
            'meterParameter',
            function ($q) use ($cityId) {
                $q->whereHas(
                    'address',
                    function ($q) use ($cityId) {
                        $q->where('city_id', $cityId);
                    }
                );
            }
        )->get();
        $city['meters'] = $meters;
        $city['metersCount'] = count($meters);
        return $city;
    }

    public function getMetersInClusterWithConnectionType($clusterId, $connectionTypeId)
    {
        return $this->meter->newQuery()->whereHas(
            'meterParameter',
            static function ($q) use ($clusterId, $connectionTypeId) {
                //meter.meter_parameter
                $q->where('connection_group_id', $connectionTypeId)
                    ->whereHas(
                        'address',
                        function ($q) use ($clusterId) {
                            //meter.meter_parameter.address
                            $q->whereHas(
                                'city',
                                function ($q) use ($clusterId) {
                                    //meter.meter_parameter.address.city
                                    $q->where('cluster_id', $clusterId);
                                }
                            );
                        }
                    );
            }
        )
            ->get();
    }

    public function getMetersInMiniGrid($miniGridId)
    {
        return $this->meter->newQuery()->whereHas(
            'meterParameter',
            static function ($q) use ($miniGridId) {
                //meter.meter_parameter
                $q->whereHas(
                    'address',
                    function ($q) use ($miniGridId) {
                        //meter.meter_parameter.address
                        $q->whereHas(
                            'city',
                            function ($q) use ($miniGridId) {
                                //meter.meter_parameter.address.city
                                $q->where('mini_grid_id', $miniGridId);
                            }
                        );
                    }
                );
            }
        )
            ->get();
    }

    public function getMetersInCityWithConnectionType(City $city, $connectionTypeId): City
    {
        $cityId = $city->id;
        $meters = $this->meter->newQuery()->hereHas(
            'meterParameter',
            static function ($q) use ($cityId, $connectionTypeId) {
                $q->where('connection_type_id', $connectionTypeId)
                    ->whereHas(
                        'address',
                        function ($q) use ($cityId) {
                            $q->where('city_id', $cityId);
                        }
                    );
            }
        )->get();
        $city['meters'] = $meters;
        $city['metersCount'] = count($meters);
        return $city;
    }

    public function updateMeterGeoLocations(array $meters): array
    {
        try {
            foreach ($meters as $key => $meter) {
                $points = [
                    $meter['lat'],
                    $meter['lng']
                ];
                if ($points) {
                    $meter = $this->meter->find($meter['id']);
                    $geo = $meter->meterParameter()->first()->address()->first()->geo()->first();
                    $geo->points = $points[0] . ',' . $points[1];
                    $geo->save();
                }
            }
            return ['data' => true];
        } catch (Exception $exception) {
            throw  new Exception($exception->getMessage());
        }
    }

    public function create($meterData)
    {
        return $this->meter->newQuery()->create([
            'serial_number' => $meterData['serial_number'],
            'meter_type_id' => $meterData['meter_type_id'],
            'in_use' => $meterData['in_use'],
            'manufacturer_id' => $meterData['manufacturer_id'],
        ]);
    }

    public function getById($meterId)
    {
        return $this->meter->newQuery()->with([
                'meterParameter.tariff',
                'meterParameter.owner',
                'meterType',
                'meterParameter.connectionType',
                'meterParameter.connectionGroup',
                'manufacturer'
            ])->find($meterId);
    }

    public function delete($meter)
    {
        return $meter->delete();
    }

    public function getAll($limit = null, $inUse = true)
    {
        if (isset($inUse)) {
            return $this->meter->newQuery()->with('meterType', 'meterParameter.tariff')->where(
                'in_use',
                $inUse
            )->paginate($limit);
        }
        return $this->meter->newQuery()->with('meterType', 'meterParameter.tariff')->paginate($limit);
    }

    public function update($meter, $meterData)
    {
        $meter->update($meterData);
        $meter->fresh();

        return $meter;
    }
}
