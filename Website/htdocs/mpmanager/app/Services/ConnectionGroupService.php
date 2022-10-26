<?php

namespace App\Services;

use App\Models\ConnectionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ConnectionGroupService implements IBaseService
{
    public function __construct(private ConnectionGroup $connectionGroup)
    {
    }

    public function create($connectionGroupData)
    {
        return $this->connectionGroup->newQuery()->create($connectionGroupData);
    }

    public function getById($connectionGroupId)
    {
        return $this->connectionGroup->newQuery()->findOrFail($connectionGroupId);
    }

    public function update($connectionGroup, $connectionGroupData): Model|Builder
    {
        $connectionGroup->update($connectionGroupData);
        $connectionGroup->fresh();

        return $connectionGroup;
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->connectionGroup->newQuery()->paginate($limit);
        }
        return $this->connectionGroup->newQuery()->get();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
