<?php

namespace App\Services;

use App\Models\SubConnectionType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SubConnectionTypeService implements IBaseService
{
    public function __construct(private SubConnectionType $subConnectionType)
    {
    }

    public function getSubConnectionTypesByConnectionTypeId($connectionTypeId, $limit = null): LengthAwarePaginator|Collection
    {

        return $limit ? $this->subConnectionType->newQuery()->where('connection_type_id', $connectionTypeId)
            ->paginate($limit) :
            $this->subConnectionType->newQuery()->where('connection_type_id', $connectionTypeId)
            ->get();
    }

    public function getById($subConnectionTypeId)
    {
        return $this->subConnectionType->newQuery()->findOrFail($subConnectionTypeId);
    }

    public function create($subConnectionServiceData)
    {
        return $this->subConnectionType->newQuery()->create($subConnectionServiceData);
    }

    public function update($subConnectionType, $subConnectionTypeData): Model|Builder
    {
        $subConnectionType->update($subConnectionTypeData);
        $subConnectionType->fresh();

        return $subConnectionType;
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return  $this->subConnectionType->newQuery()->paginate($limit) ;
        }

        return  $this->subConnectionType->newQuery()->get();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
