<?php

namespace App\Services;

use App\Models\City;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<City>
 */
class CityService implements IBaseService {
    public function __construct(
        private City $city,
    ) {}

    /**
     * @return array<int, int>
     */
    public function getCityIdsByMiniGridId(int $miniGridId): array {
        return $this->city->newQuery()->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();
    }

    /**
     * @param string|array<string> $relation
     */
    public function getByIdWithRelation(int $cityId, string|array $relation): ?City {
        return $this->city->newQuery()->with($relation)->find($cityId);
    }

    public function getById(int $cityId): ?Model {
        return $this->city->newQuery()->find($cityId);
    }

    /**
     * @param array<string, mixed> $cityData
     */
    public function update(Model $model, array $cityData): Model {
        $model->update([
            'name' => $cityData['name'] ?? $model->name,
            'mini_grid_id' => $cityData['mini_grid_id'] ?? $model->mini_grid_id,
            'cluster_id' => $cityData['cluster_id'] ?? $model->cluster_id,
            'country_id' => $cityData['country_id'] ?? $model->country_id,
        ]);
        $model->fresh();

        return $model;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Model {
        return $this->city->newQuery()->create($data);
    }

    /**
     * @return Collection<int, City>|LengthAwarePaginator<City>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->city->newQuery()->with('location')->paginate($limit);
        }

        return $this->city->newQuery()->with('location')->get();
    }

    public function delete(Model $model): ?bool {
        throw new \Exception('not implemented');
    }
}
