<?php

namespace App\Services;

use App\Exceptions\EntityHasChildrenException;
use App\Models\GeographicalInformation;
use App\Models\MiniGrid;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MiniGrid>
 */
class MiniGridService implements IBaseService {
    /** @use HasCrudOperations<MiniGrid> */
    use HasCrudOperations;

    public function __construct(private MiniGrid $miniGrid) {}

    protected function crudModel(): MiniGrid {
        return $this->miniGrid;
    }

    public function getByIdWithLocation(int $miniGridId): ?MiniGrid {
        return $this->miniGrid->newQuery()->with(['location'])->find($miniGridId);
    }

    /**
     * @param array<string, mixed> $miniGridData
     */
    public function create(array $miniGridData): MiniGrid {
        $miniGrid = $this->miniGrid->newQuery()->create([
            'name' => $miniGridData['name'],
            'cluster_id' => $miniGridData['cluster_id'],
        ]);
        $miniGrid->location()->create(['geo_json' => GeographicalInformation::pointFromInputGeoJson($miniGridData['geo_json'])]);

        return $miniGrid;
    }

    /**
     * @param MiniGrid             $model
     * @param array<string, mixed> $miniGridData
     */
    public function update(Model $model, array $miniGridData): MiniGrid {
        $model->update([
            'name' => $miniGridData['name'] ?? $model->name,
            'cluster_id' => $miniGridData['cluster_id'] ?? $model->cluster_id,
        ]);

        if (isset($miniGridData['geo_json'])) {
            $model->location()->updateOrCreate([], ['geo_json' => GeographicalInformation::pointFromInputGeoJson($miniGridData['geo_json'])]);
        }

        return $model->load('location');
    }

    /**
     * @return Collection<int, MiniGrid>|LengthAwarePaginator<int, MiniGrid>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->miniGrid->newQuery()->with(['location']);

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    /**
     * @param MiniGrid $model
     *
     * @throws EntityHasChildrenException when the mini-grid still has cities
     */
    public function delete(Model $model): ?bool {
        if ($model->cities()->exists()) {
            throw new EntityHasChildrenException('Mini-grid cannot be deleted while it still has villages. Delete the villages first.');
        }

        return $model->delete();
    }
}
