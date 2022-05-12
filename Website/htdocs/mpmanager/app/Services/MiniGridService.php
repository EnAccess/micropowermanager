<?php

namespace App\Services;

use App\Models\MiniGrid;

class MiniGridService extends BaseService implements IBaseService
{
    public function __construct(private MiniGrid $miniGrid)
    {
        parent::__construct([$miniGrid]);
    }

    public function getByIdWithLocation($miniGridId)
    {
        return $this->miniGrid->newQuery()->with(['location'])->find($miniGridId);
    }

    public function getDataStreamEnabledMiniGridsCount(): int
    {
        return $this->miniGrid->newQuery()->where('data_stream', 1)->count();
    }

    public function getById($miniGridId)
    {
        return $this->miniGrid->newQuery()->find($miniGridId);
    }

    public function create($miniGridData)
    {
        return $this->miniGrid->newQuery()->create($miniGridData);
    }

    public function update($miniGrid, $miniGridData)
    {
         $miniGrid->update([
            'name' => $miniGridData['name'] ?? $miniGrid->name,
            'data_stream' => $miniGridData['data_stream'] ?? $miniGrid->data_stream,
        ]);
         $miniGrid->fresh();

         return $miniGrid;
    }

    public function getAll($limit = null,$dataStream = null)
    {
        $miniGrids = $this->miniGrid->newQuery();
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