<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterService;
use App\Services\MiniGridRevenueService;
use Illuminate\Http\Request;

class MiniGridRevenueController {
    public function __construct(
        private MeterService $meterService,
        private MiniGridRevenueService $miniGridRevenueService,
    ) {}

    public function show(int $miniGridId, Request $request): ApiResource {
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        if (preg_match('/\/energy/', $request->url())) {
            return ApiResource::make($this->miniGridRevenueService->getSoldEnergyById(
                $miniGridId,
                $startDate,
                $endDate,
                $this->meterService
            ));
        }

        return ApiResource::make($this->miniGridRevenueService->getById(
            $miniGridId,
            $startDate,
            $endDate,
            $this->meterService
        ));
    }
}
