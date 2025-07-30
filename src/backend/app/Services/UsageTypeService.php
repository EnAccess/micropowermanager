<?php

namespace App\Services;

use App\Models\UsageType;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<UsageType>
 */
class UsageTypeService implements IBaseService {
    public function __construct(
        private UsageType $usageType,
    ) {}

    /**
     * @return Collection<int, UsageType>|LengthAwarePaginator<UsageType>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->usageType->newQuery()->paginate($limit);
        }

        return $this->usageType->newQuery()->get();
    }

    public function getById(int $id): UsageType {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): UsageType {
        throw new \Exception('Method create() not yet implemented.');
    }

    public function update($model, array $data): UsageType {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
