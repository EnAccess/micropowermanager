<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterTypeMeterService;
use Illuminate\Http\Request;

class MeterTypeMeterController extends Controller {
    public function __construct(private MeterTypeMeterService $meterTypeMeterService) {}

    /**
     * List meters of a meter type.
     *
     * Displays the meter type with its associated meters.
     *
     * @return ApiResource
     */
    public function show(Request $request, int $meterTypeId) {
        return ApiResource::make($this->meterTypeMeterService->getByIdWithMeters($meterTypeId));
    }
}
