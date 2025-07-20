<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterConsumptionService;
use App\Services\MeterService;

class MeterConsumptionController extends Controller {
    public function __construct(
        private MeterService $meterService,
        private MeterConsumptionService $meterConsumptionService,
    ) {}

    /**
     * Consumption List
     * If the meter has the ability to send data to your server. That is the endpoint where you get the
     * meter readings ( used energy, credit on meter etc.).
     *
     * @urlParam     serialNumber
     * @urlParam     start YYYY-mm-dd format
     * @urlParam     end YYYY-mm-dd format
     *
     * @responseFile responses/meters/meter.consumption.list.json
     *
     * @param $serialNumber
     * @param $start
     * @param $end
     *
     * @return ApiResource
     */
    public function show(string $serialNumber, string $start, string $end): ApiResource {
        $meter = $this->meterService->getBySerialNumber($serialNumber);

        return ApiResource::make($this->meterConsumptionService->getByMeter($meter, $start, $end));
    }
}
