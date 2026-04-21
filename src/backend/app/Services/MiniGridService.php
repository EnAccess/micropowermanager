<?php

namespace App\Services;

use App\Exceptions\EntityHasChildrenException;
use App\Models\MiniGrid;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MiniGrid>
 */
class MiniGridService implements IBaseService {
    public function __construct(private MiniGrid $miniGrid) {}

    public function getByIdWithLocation(int $miniGridId): ?MiniGrid {
        return $this->miniGrid->newQuery()->with(['location'])->find($miniGridId);
    }

    public function getById(int $miniGridId): ?MiniGrid {
        return $this->miniGrid->newQuery()->find($miniGridId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): MiniGrid {
        return $this->miniGrid->newQuery()->create($data);
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

        return $model->fresh();
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
