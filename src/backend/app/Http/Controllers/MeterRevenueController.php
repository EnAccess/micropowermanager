<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterRevenueService;

class MeterRevenueController extends Controller {
    public function __construct(
        private MeterRevenueService $meterRevenueService,
    ) {}

    /**
     * Get meter revenue.
     *
     * The total revenue that the meter made.
     */
    public function show(string $serialNumber): ApiResource {
        $revenue = $this->meterRevenueService->getBySerialNumber($serialNumber);

        return ApiResource::make(['revenue' => $revenue]);
    }
}
