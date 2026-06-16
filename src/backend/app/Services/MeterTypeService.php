<?php

namespace App\Services;

use App\Models\Meter\MeterType;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<MeterType>
 */
class MeterTypeService implements IBaseService {
    /** @use HasCrudOperations<MeterType> */
    use HasCrudOperations;

    public function __construct(private MeterType $meterType) {}

    protected function crudModel(): MeterType {
        return $this->meterType;
    }

    public function getById(int $meterTypeId): MeterType {
        return $this->meterType->newQuery()->findOrFail($meterTypeId);
    }
}
