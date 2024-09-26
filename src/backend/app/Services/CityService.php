<?php

namespace App\Services;

use App\Models\City;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<City>
 */
class CityService implements IBaseService
{
    public function __construct(
        private City $city
    ) {
    }

    public function getCityIdsByMiniGridId($miniGridId): array
    {
        return $this->city->newQuery()->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();
    }

    public function getByIdWithRelation($cityId, $relation)
    {
        return $this->city->newQuery()->with($relation)->find($cityId);
    }

    public function getById($cityId): City
    {
        return $this->city->newQuery()->find($cityId);
    }

    public function update($city, array $cityData): City
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

    public function create(array $data): City
    {
        return $this->city->newQuery()->create($data);
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator
    {
        if ($limit) {
            return $this->city->newQuery()->with('location')->paginate($limit);
        }

        return $this->city->newQuery()->with('location')->get();
    }

    public function delete($model): ?bool
    {
        throw new \Exception('not implemented');
    }
}
