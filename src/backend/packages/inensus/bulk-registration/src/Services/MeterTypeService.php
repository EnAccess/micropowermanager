<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Meter\MeterType;

class MeterTypeService {
    public function __construct(private MeterType $meterType) {}

    public function createDefaultMeterTypeIfDoesNotExistAny() {
        return $this->meterType->newQuery()->firstOrCreate(['id' => 1]);
    }
}
