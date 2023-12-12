<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\MiniGrid;

class MiniGridService implements IBaseService
{
    public function __construct(private MiniGrid $miniGrid, private Cluster $cluster)
    {
    }

    public function getByIdWithLocation($miniGridId)
    {
        return $this->miniGrid->newQuery()->with(['location'])->find($miniGridId);
    }

    public function getDataStreamEnabledMiniGridsCount(): int
    {
        return $this->miniGrid->newQuery()->where('data_stream', 1)->count();
    }

    public function getById($miniGridId): MiniGrid
    {
        /** @var MiniGrid $model */
        $model = $this->miniGrid->newQuery()->find($miniGridId);
        return $model;
    }

    public function create($miniGridData): MiniGrid
    {
        /** @var MiniGrid $result */
        $result = $this->miniGrid->newQuery()->create($miniGridData);

        return $result;
    }

    public function update($miniGrid, $miniGridData): MiniGrid
    {
        $miniGrid->update([
            'name' => $miniGridData['name'] ?? $miniGrid->name,
            'data_stream' => $miniGridData['data_stream'] ?? $miniGrid->data_stream,
        ]);
        $miniGrid->fresh();

        return $miniGrid;
    }

    public function getAll($limit = null, $dataStream = null)
    {
        $miniGrids = $this->miniGrid->newQuery()->with(['location']);
        if ($dataStream) {
            $miniGrids->where('data_stream', $dataStream);
        }

        if ($limit) {
            return $miniGrids->newQuery()->paginate($limit);
        }

        return $miniGrids->newQuery()->get();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
