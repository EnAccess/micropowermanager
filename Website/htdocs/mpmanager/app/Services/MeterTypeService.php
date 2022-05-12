<?php

namespace App\Services;

use App\Models\Meter\MeterType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeterTypeService extends BaseService implements IBaseService
{
    use SoftDeletes;

    public function __construct(private MeterType $meterType)
    {
        parent::__construct([$meterType]);
    }

    public function create($meterTypeData)
    {
        return $this->meterType->newQuery()->create($meterTypeData);
    }

    public function getById($meterTypeId)
    {
        return $this->meterType->newQuery()->findOrFail($meterTypeId);
    }

    public function update($meterType, $meterTypeData)
    {
        $meterType->update($meterTypeData);
        $meterType->fresh();

        return $meterType;
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->meterType->newQuery()->paginate($limit);
        }
        return $this->meterType->newQuery()->get();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}