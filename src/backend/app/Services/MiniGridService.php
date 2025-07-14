<?php

namespace App\Services;

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
     * @param array<string, mixed> $miniGridData
     */
    public function update(Model $model, array $miniGridData): MiniGrid {
        /* @var MiniGrid $model */
        $model->update([
            'name' => $miniGridData['name'] ?? $model->name,
        ]);
        $model->fresh();

        return $model;
    }

    /**
     * @return Collection<int, MiniGrid>|LengthAwarePaginator<MiniGrid>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->miniGrid->newQuery()->with(['location']);

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    public function delete(Model $model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
