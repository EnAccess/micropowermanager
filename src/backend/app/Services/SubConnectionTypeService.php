<?php

namespace App\Services;

use App\Models\SubConnectionType;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<SubConnectionType>
 */
class SubConnectionTypeService implements IBaseService {
    public function __construct(
        private SubConnectionType $subConnectionType,
    ) {}

    /**
     * @return LengthAwarePaginator<SubConnectionType>|Collection<int, SubConnectionType>
     */
    public function getSubConnectionTypesByConnectionTypeId(int $connectionTypeId, ?int $limit = null): LengthAwarePaginator|Collection {
        return $limit ? $this->subConnectionType->newQuery()->where('connection_type_id', $connectionTypeId)
            ->paginate($limit) :
            $this->subConnectionType->newQuery()->where('connection_type_id', $connectionTypeId)
                ->get();
    }

    public function getById(int $subConnectionTypeId): SubConnectionType {
        return $this->subConnectionType->newQuery()->findOrFail($subConnectionTypeId);
    }

    /**
     * @param array<string, mixed> $subConnectionServiceData
     */
    public function create(array $subConnectionServiceData): SubConnectionType {
        return $this->subConnectionType->newQuery()->create($subConnectionServiceData);
    }

    /**
     * @param array<string, mixed> $subConnectionTypeData
     */
    public function update($subConnectionType, array $subConnectionTypeData): SubConnectionType {
        $subConnectionType->update($subConnectionTypeData);
        $subConnectionType->fresh();

        return $subConnectionType;
    }

    /**
     * @return Collection<int, SubConnectionType>|LengthAwarePaginator<SubConnectionType>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->subConnectionType->newQuery()->paginate($limit);
        }

        return $this->subConnectionType->newQuery()->get();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
