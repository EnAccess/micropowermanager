<?php

namespace App\Services;

use App\Models\MiniGrid;

class MiniGridService extends BaseService
{
    public function __construct(private MiniGrid $miniGrid)
    {
        parent::__construct([$miniGrid]);
    }

    public function getMiniGrids($dataStream = null)
    {
        $miniGrids = $this->miniGrid->newQuery();
        if ($dataStream) {
            $miniGrids->where('data_stream', $dataStream);
        }
        return $miniGrids->get();
    }

    public function getById($miniGridId)
    {
        return $this->miniGrid->newQuery()->find($miniGridId);
    }

    public function getByIdWithLocation($miniGridId)
    {
        return $this->miniGrid->newQuery()->with(['location'])->find($miniGridId);
    }

    public function create($miniGridData)
    {
        return $this->miniGrid->newQuery()->create($miniGridData);
    }

    public function update($miniGrid, $miniGridData)
    {
        return $miniGrid->update([
            'name' => $miniGridData['name'] ?? $miniGrid->name,
            'data_stream' => $miniGridData['data_stream'] ?? $miniGrid->data_stream,
        ]);
    }

    public function getDataStreamEnabledMiniGridsCount(): int
    {
        return $this->miniGrid->newQuery()->where('data_stream', 1)->count();
    }

}