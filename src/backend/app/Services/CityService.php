<?php

namespace App\Services;

use App\Exceptions\EntityHasChildrenException;
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
     * @return Collection<int, City>|LengthAwarePaginator<int, City>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->city->newQuery()->with(['location', 'miniGrid', 'country']);

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    /**
     * @param City $model
     *
     * @throws EntityHasChildrenException when the city still has addresses
     */
    public function delete(Model $model): ?bool {
        if ($model->addresses()->exists()) {
            throw new EntityHasChildrenException('Village cannot be deleted while it still has addresses linked to it.');
        }

        return $model->delete();
    }
}
