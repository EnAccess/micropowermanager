<?php

namespace App\Services;

use App\Models\ConnectionGroup;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<ConnectionGroup>
 */ class ConnectionGroupService implements IBaseService
{
    public function __construct(
        private ConnectionGroup $connectionGroup
    ) {
    }

    public function create(array $connectionGroupData): ConnectionGroup
    {
        return $this->connectionGroup->newQuery()->create($connectionGroupData);
    }

    public function getById(int $connectionGroupId): ConnectionGroup
    {
        return $this->connectionGroup->newQuery()->findOrFail($connectionGroupId);
    }

    public function update($connectionGroup, array $connectionGroupData): ConnectionGroup
    {
        $connectionGroup->update($connectionGroupData);
        $connectionGroup->fresh();

        return $connectionGroup;
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator
    {
        if ($limit) {
            return $this->connectionGroup->newQuery()->paginate($limit);
        }

        return $this->connectionGroup->newQuery()->get();
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
