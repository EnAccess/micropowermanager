<?php

namespace App\Services;

use App\Models\ConnectionType;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<ConnectionType>
 */
class ConnectionTypeService implements IBaseService {
    public function __construct(
        private ConnectionType $connectionType,
    ) {}

    /**
     * @param int|string $connectionTypeId
     */
    public function getByIdWithMeterCountRelation($connectionTypeId): Model|Builder {
        return $this->connectionType->newQuery()->withCount('meters')->where('id', $connectionTypeId)
            ->firstOrFail();
    }

    public function getById(int $connectionTypeId): ConnectionType {
        return $this->connectionType->newQuery()->findOrFail($connectionTypeId);
    }

    /**
     * @param array<string, mixed> $connectionServiceData
     */
    public function create(array $connectionServiceData): ConnectionType {
        return $this->connectionType->newQuery()->create($connectionServiceData);
    }

    /**
     * @param array<string, mixed> $connectionTypeData
     */
    public function update($connectionType, array $connectionTypeData): ConnectionType {
        $connectionType->update($connectionTypeData);
        $connectionType->fresh();

        return $connectionType;
    }

    /**
     * @return Collection<int, ConnectionType>|LengthAwarePaginator<ConnectionType>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->connectionType->newQuery()->paginate($limit);
        }

        return $this->connectionType->newQuery()->get();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
