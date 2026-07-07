<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\PersonMeterService;

class PersonMeterController {
    public function __construct(
        private PersonMeterService $personMeterService,
    ) {}

    /**
     * Get person meters with tariffs.
     *
     * Person details with his/her owned meter(s) and its assigned tariff.
     */
    public function show(int $personId): ApiResource {
        return ApiResource::make($this->personMeterService->getPersonMeters($personId));
    }
}
