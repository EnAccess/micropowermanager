<?php

namespace App\Services;

use App\Models\ConnectionType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;


class ConnectionTypeService extends BaseService
{
    public function __construct(private ConnectionType $connectionType)
    {
        parent::__construct([$connectionType]);
    }

    public function getConnectionTypes($limit = null): LengthAwarePaginator|Collection
    {

        return $limit ? $this->connectionType->newQuery()->paginate($limit) : $this->connectionType->newQuery()->get();
    }

    public function getById($connectionTypeId): Model|Builder
    {
        return $this->connectionType->newQuery()->findOrFail($connectionTypeId);
    }

    public function getByIdWithMeterCountRelation($connectionTypeId): Model|Builder
    {
        return $this->connectionType->newQuery()->withCount('meterParameters')->where('id', $connectionTypeId)
            ->firstOrFail();

    }

    public function create($connectionServiceData)
    {
        return $this->connectionType->newQuery()->create($connectionServiceData);
    }

    public function update($connectionType, $connectionTypeData): Model|Builder
    {
        $connectionType->update($connectionTypeData);

        return $connectionType;
    }
}
