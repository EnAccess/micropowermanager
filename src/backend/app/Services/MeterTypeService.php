<?php

namespace App\Services;

use App\Models\Meter\MeterType;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MeterType>
 */
class MeterTypeService implements IBaseService {
    public function __construct(private MeterType $meterType) {}

    public function create(array $meterTypeData): MeterType {
        return $this->meterType->newQuery()->create($meterTypeData);
    }

    public function getById(int $meterTypeId): MeterType {
        return $this->meterType->newQuery()->findOrFail($meterTypeId);
    }

    public function update($meterType, array $meterTypeData): MeterType {
        $meterType->update($meterTypeData);
        $meterType->fresh();

        return $meterType;
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->meterType->newQuery()->paginate($limit);
        }

        return $this->meterType->newQuery()->get();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
