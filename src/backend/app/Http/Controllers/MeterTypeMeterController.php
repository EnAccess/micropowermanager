<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterTypeMeterService;
use Illuminate\Http\Request;

class MeterTypeMeterController extends Controller {
    public function __construct(private MeterTypeMeterService $meterTypeMeterService) {}

    /**
     * List with Meters
     * Displays the meter types with the associated meters.
     *
     * @urlParam id required
     *
     * @responseFile responses/metertypes/metertypes.meter.list.json
     *
     * @param Request $request
     * @param         $meterTypeId
     *
     * @return ApiResource
     */
    public function show(Request $request, int $meterTypeId) {
        return ApiResource::make($this->meterTypeMeterService->getByIdWithMeters($meterTypeId));
    }
}
