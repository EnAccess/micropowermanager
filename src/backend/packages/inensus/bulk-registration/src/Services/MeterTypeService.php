<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Meter\MeterType;

class MeterTypeService {
    private MeterType $meterType;

    public function __construct(MeterType $meterType) {
        $this->meterType = $meterType;
    }

    public function createDefaultMeterTypeIfDoesNotExistAny() {
        return $this->meterType->newQuery()->firstOrCreate(['id' => 1]);
    }
}
