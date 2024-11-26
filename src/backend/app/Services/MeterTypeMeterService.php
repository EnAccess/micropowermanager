<?php

namespace App\Services;

use App\Models\Meter\MeterType;

class MeterTypeMeterService {
    public function __construct(private MeterType $meterType) {}

    public function getByIdWithMeters($meterTypeId) {
        return $this->meterType->newQuery()->with(['meters'])->findOrFail($meterTypeId);
    }
}
