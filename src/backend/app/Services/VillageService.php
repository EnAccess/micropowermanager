<?php

namespace App\Services;

use App\Models\Village;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Village>
 */
class VillageService implements IBaseService {
    public function __construct(
        private Village $village,
    ) {}

    /**
     * @return array<int, int>
     */
    public function getVillageIdsByMiniGridId(int $miniGridId): array {
        return $this->village->newQuery()->select('id')->where('mini_grid_id', $miniGridId)->get()->pluck('id')->toArray();
    }

    /**
     * @param string|array<string> $relation
     */
    public function getByIdWithRelation(int $villageId, string|array $relation): ?Village {
        return $this->village->newQuery()->with($relation)->find($villageId);
    }

    public function getById(int $villageId): ?Model {
        return $this->village->newQuery()->find($villageId);
    }

    /**
     * @param array<string, mixed> $villageData
     */
    public function update(Model $model, array $villageData): Model {
        $model->update([
            'name' => $villageData['name'] ?? $model->name,
            'mini_grid_id' => $villageData['mini_grid_id'] ?? $model->mini_grid_id,
            'country_id' => $villageData['country_id'] ?? $model->country_id,
        ]);
        $model->fresh();

        return $model;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Model {
        return $this->village->newQuery()->create($data);
    }

    /**
     * @return Collection<int, Village>|LengthAwarePaginator<int, Village>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->village->newQuery()->with('location')->paginate($limit);
        }

        return $this->village->newQuery()->with('location')->get();
    }

    public function delete(Model $model): ?bool {
        throw new \Exception('not implemented');
    }
}
