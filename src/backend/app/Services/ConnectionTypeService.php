<?php

namespace App\Services;

use App\Models\ConnectionType;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * @implements IBaseService<ConnectionType>
 */
class ConnectionTypeService implements IBaseService {
    /** @use HasCrudOperations<ConnectionType> */
    use HasCrudOperations;

    public function __construct(
        private ConnectionType $connectionType,
    ) {}

    protected function crudModel(): ConnectionType {
        return $this->connectionType;
    }

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
}
