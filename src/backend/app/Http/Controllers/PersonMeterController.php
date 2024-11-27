<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\PersonMeterService;

class PersonMeterController {
    public function __construct(
        private PersonMeterService $personMeterService,
    ) {}

    /**
     * @group    People
     * Person with Meters & Tariff
     * Person details with his/her owned meter(s) and its assigned tariff
     *
     * @param int $personId
     *
     * @urlParam person required The ID of the person
     *
     * @return ApiResource
     *
     * @responseFile responses/people/person.meter.tariff.json
     */
    public function show(int $personId): ApiResource {
        return ApiResource::make($this->personMeterService->getPersonMeters($personId));
    }
}
