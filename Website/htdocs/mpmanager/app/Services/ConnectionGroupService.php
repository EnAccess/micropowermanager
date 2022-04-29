<?php

namespace App\Services;

use App\Models\ConnectionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ConnectionGroupService extends BaseService
{
    public function __construct(private ConnectionGroup $connectionGroup)
    {
        parent::__construct([$connectionGroup]);
    }

    public function getConnectionGroupList(): Collection|array
    {
        return $this->connectionGroup->newQuery()->get();
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

        return $connectionGroup;
    }


}