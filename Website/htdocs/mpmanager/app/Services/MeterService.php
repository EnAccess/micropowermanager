<?php



namespace App\Services;

use App\Models\City;
use App\Models\Meter\Meter;
use Exception;
use Illuminate\Support\Facades\Log;

use function count;

class MeterService
{


    public function __construct(private SessionService $sessionService, private Meter $meter)
    {
       $this->sessionService->setModel($meter);
    }


    public function getMeterCountInCluster($clusterId)
    {
        return $this->meter::whereHas(
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
        return $this->meter::whereHas(
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
        return $this->meter::whereHas(
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
        $meters = $this->meter::whereHas(
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
        $meters = $this->meter::whereHas(
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

    public function getMetersInClusterwithConnectionType($clusterId, $connectionTypeId)
    {
        return $this->meter::whereHas(
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
        return $this->meter::whereHas(
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
        $meters = $this->meter::whereHas(
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


    /**
     * @param array $meters
     * @return true[]
     *
     * @throws Exception
     * @psalm-return array{data: true}
     */
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
}
