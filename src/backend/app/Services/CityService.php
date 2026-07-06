<?php

namespace App\Services;

use App\Exceptions\EntityHasChildrenException;
use App\Models\City;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<City>
 */
class CityService implements IBaseService {
    /** @use HasCrudOperations<City> */
    use HasCrudOperations;

    public function __construct(
        private City $city,
    ) {}

    protected function crudModel(): City {
        return $this->city;
    }

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

    /**
     * @param array<string, mixed> $cityData
     */
    public function create(array $cityData): City {
        $city = $this->city->newQuery()->create([
            'name' => $cityData['name'],
            'mini_grid_id' => $cityData['mini_grid_id'],
            'country_id' => $cityData['country_id'],
        ]);
        $city->location()->create(['geo_json' => GeographicalInformation::pointFromInputGeoJson($cityData['geo_json'])]);

        return $city;
    }

    /**
     * @param City                 $model
     * @param array<string, mixed> $cityData
     */
    public function update(Model $model, array $cityData): Model {
        $model->update([
            'name' => $cityData['name'] ?? $model->name,
            'mini_grid_id' => $cityData['mini_grid_id'] ?? $model->mini_grid_id,
            'country_id' => $cityData['country_id'] ?? $model->country_id,
        ]);

        if (isset($cityData['geo_json'])) {
            $model->location()->updateOrCreate([], ['geo_json' => GeographicalInformation::pointFromInputGeoJson($cityData['geo_json'])]);
        }

        return $model->load('location');
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
