<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 2019-03-13
 * Time: 19:24
 */

namespace App\Http\Services;

use App\Models\City;
use App\Models\Person\Person;
use App\Services\BaseService;
use App\Services\SessionService;

class CityService extends  BaseService
{


    public function __construct(private City $city, private Person $person)
    {
        parent::__construct([$city,$person]);
    }

    public function getCityPopulation($cityId, $onlyCustomers = true)
    {
        if ($onlyCustomers) {
            $population = $this->person
                ->where('is_customer', 1)
                ->whereHas(
                    'addresses',
                    function ($q) use ($cityId) {
                        $q->where('city_id', $cityId)->where('is_primary', 1);
                    }
                )->count();
        } else {
            $population = $this->person->whereHas(
                'addresses',
                function ($q) use ($cityId) {
                    $q->where('city_id', $cityId)->where('is_primary', 1);
                }
            )->count();
        }

        return $population;
    }

    public function getClusteropulation($clusterId, $onlyCustomers = true)
    {
        if ($onlyCustomers) {
            $population = $this->person
                ->where('is_customer', 1)
                ->whereHas(
                    'addresses',
                    function ($q) use ($clusterId) {
                        $q->where('is_primary', 1)->whereHas(
                            'city',
                            function ($q) use ($clusterId) {
                                $q->where('cluster_id', $clusterId);
                            }
                        );
                    }
                )->count();
        } else {
            $population = $this->person->whereHas(
                'addresses',
                function ($q) use ($clusterId) {
                    $q->where('is_primary', 1)->whereHas(
                        'city',
                        function ($q) use ($clusterId) {
                            $q->where('cluster_id', $clusterId);
                        }
                    );
                }
            )->count();
        }


        return $population;
    }

    public function getCityIdsByMiniGridId($miniGridId): array
    {
        return
            $this->city->newQuery()->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();
    }
}
