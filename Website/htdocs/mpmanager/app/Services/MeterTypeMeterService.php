<?php

namespace App\Services;

use App\Models\Meter\MeterType;

class MeterTypeMeterService extends BaseService
{
    public function __construct(private MeterType $meterType)
    {
        parent::__construct([$meterType]);
    }

    public function getByIdWithMeters($meterTypeId)
    {
        return $this->meterType->newQuery()->with(['meters'])->findOrFail($meterTypeId);
    }
}