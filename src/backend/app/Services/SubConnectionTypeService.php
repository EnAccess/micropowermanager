<?php

namespace App\Services;

use App\Models\SubConnectionType;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<SubConnectionType>
 */
class SubConnectionTypeService implements IBaseService {
    /** @use HasCrudOperations<SubConnectionType> */
    use HasCrudOperations;

    public function __construct(
        private SubConnectionType $subConnectionType,
    ) {}

    protected function crudModel(): SubConnectionType {
        return $this->subConnectionType;
    }

    /**
     * @return LengthAwarePaginator<int, SubConnectionType>|Collection<int, SubConnectionType>
     */
    public function getSubConnectionTypesByConnectionTypeId(int $connectionTypeId, ?int $limit = null): LengthAwarePaginator|Collection {
        return $limit ? $this->subConnectionType->newQuery()->where('connection_type_id', $connectionTypeId)
            ->with('tariff')
            ->paginate($limit) :
            $this->subConnectionType->newQuery()->where('connection_type_id', $connectionTypeId)
                ->with('tariff')
                ->get();
    }

    public function getById(int $subConnectionTypeId): SubConnectionType {
        return $this->subConnectionType->newQuery()->with('tariff')->findOrFail($subConnectionTypeId);
    }

    /**
     * @param array<string, mixed> $subConnectionTypeData
     */
    public function update($subConnectionType, array $subConnectionTypeData): SubConnectionType {
        $subConnectionType->update($subConnectionTypeData);
        $subConnectionType->fresh(['tariff']);

        return $subConnectionType;
    }
}
