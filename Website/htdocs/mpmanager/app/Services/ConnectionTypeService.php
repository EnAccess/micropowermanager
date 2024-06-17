<?php

namespace App\Services;

use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class ConnectionTypeService implements IBaseService
{
    public function __construct(private ConnectionType $connectionType)
    {
    }

    public function getByIdWithMeterCountRelation($connectionTypeId): Model|Builder
    {
        return $this->connectionType->newQuery()->withCount('meterParameters')->where('id', $connectionTypeId)
            ->firstOrFail();
    }

    public function getById($connectionTypeId)
    {
        return $this->connectionType->newQuery()->findOrFail($connectionTypeId);
    }

    public function create($connectionServiceData)
    {
        return $this->connectionType->newQuery()->create($connectionServiceData);
    }

    public function update($connectionType, $connectionTypeData)
    {
        $connectionType->update($connectionTypeData);
        $connectionType->fresh();

        return $connectionType;
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->connectionType->newQuery()->paginate($limit);
        }

        return $this->connectionType->newQuery()->get();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
