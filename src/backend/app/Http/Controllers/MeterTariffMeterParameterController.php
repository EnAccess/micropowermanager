<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterTariffMeterParameterService;

class MeterTariffMeterParameterController extends Controller
{
    public function __construct(
        private MeterTariffMeterParameterService $meterTariffMeterParameterService,
    ) {
    }

    /**
     * Display a list of meters which using a particular tariff.
     *
     * @param $meterTariffId
     *
     * @return ApiResource
     */
    public function show($meterTariffId): ApiResource
    {
        return ApiResource::make($this->meterTariffMeterParameterService->getCountById($meterTariffId));
    }

    /**
     * @param     $meterTariffId
     * @param int $changeId
     *
     * @return ApiResource
     */
    public function update($meterTariffId, int $changeId): ApiResource
    {
        $result = $this->meterTariffMeterParameterService->changeMetersTariff($meterTariffId, $changeId);

        return ApiResource::make($result);
    }

    public function updateForMeter($meterSerial, $tariffId): ApiResource
    {
        $result = $this->meterTariffMeterParameterService->changeMeterTariff($meterSerial, $tariffId);

        return ApiResource::make($result);
    }
}
