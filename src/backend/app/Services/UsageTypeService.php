<?php

namespace App\Services;

use App\Models\UsageType;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<UsageType>
 */
class UsageTypeService implements IBaseService
{
    public function __construct(
        private UsageType $usageType,
    ) {
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator
    {
        return $this->usageType->newQuery()->get();
    }

    public function getById(int $id): UsageType
    {
        throw new \Exception('Method getById() not yet implemented.');

        return new UsageType();
    }

    public function create(array $data): UsageType
    {
        throw new \Exception('Method create() not yet implemented.');

        return new UsageType();
    }

    public function update($model, array $data): UsageType
    {
        throw new \Exception('Method update() not yet implemented.');

        return new UsageType();
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
