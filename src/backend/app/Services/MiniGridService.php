<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\MiniGrid;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MiniGrid>
 */
class MiniGridService implements IBaseService {
    public function __construct(
        private MiniGrid $miniGrid,
        private Cluster $cluster,
    ) {}

    public function getByIdWithLocation($miniGridId) {
        return $this->miniGrid->newQuery()->with(['location'])->find($miniGridId);
    }

    public function getById($miniGridId): MiniGrid {
        /** @var MiniGrid $model */
        $model = $this->miniGrid->newQuery()->find($miniGridId);

        return $model;
    }

    public function create($miniGridData): MiniGrid {
        /** @var MiniGrid $result */
        $result = $this->miniGrid->newQuery()->create($miniGridData);

        return $result;
    }

    public function update($miniGrid, $miniGridData): MiniGrid {
        $miniGrid->update([
            'name' => $miniGridData['name'] ?? $miniGrid->name,
        ]);
        $miniGrid->fresh();

        return $miniGrid;
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $miniGrids = $this->miniGrid->newQuery()->with(['location']);

        if ($limit) {
            return $miniGrids->newQuery()->paginate($limit);
        }

        return $miniGrids->newQuery()->get();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
