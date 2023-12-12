<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 2019-03-13
 * Time: 19:24
 */

namespace App\Services;

use App\Models\City;
use App\Models\Cluster;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Services\SessionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class CityService implements IBaseService
{
    public function __construct(private City $city)
    {
    }
    public function getCityIdsByMiniGridId($miniGridId): array
    {
        return
            $this->city->newQuery()->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();
    }

    public function getByIdWithRelation($cityId, $relation)
    {
        return $this->city->newQuery()->with($relation)->find($cityId);
    }

    public function getById($cityId)
    {
        return $this->city->newQuery()->find($cityId);
    }

    public function update($city, $cityData)
    {
        $city->update([
            'name' => $cityData['name'] ?? $city->name,
            'mini_grid_id' => $cityData['mini_grid_id'] ?? $city->mini_grid_id,
            'cluster_id' => $cityData['cluster_id'] ?? $city->mini_grid_id,
            'country_id' => $cityData['country_id'] ?? $city->country_id,
        ]);
        $city->fresh();

        return $city;
    }

    public function create($data)
    {
        return $this->city->newQuery()->create($data);
    }
    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->city->newQuery()->with('location')->paginate($limit);
        }
        return $this->city->newQuery()->with('location')->get();
    }

    public function delete($model)
    {
        throw new \Exception("not implemented");
    }
}
