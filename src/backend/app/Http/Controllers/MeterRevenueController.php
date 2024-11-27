<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterRevenueService;

class MeterRevenueController extends Controller {
    public function __construct(
        private MeterRevenueService $meterRevenueService,
    ) {}

    /**
     * Revenue
     * The total revenue that the meter made.
     *
     * @group     Meters
     *
     * @bodyParam serialNumber string required.
     *
     * @responseFile responses/meters/meter.revenue.json
     *
     * @param $serialNumber
     *
     * @return ApiResource
     */
    public function show(string $serialNumber): ApiResource {
        $revenue = $this->meterRevenueService->getBySerialNumber($serialNumber);

        return ApiResource::make(['revenue' => $revenue]);
    }
}
