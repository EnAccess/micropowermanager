<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterConsumptionService;
use App\Services\MeterService;
use Dedoc\Scramble\Attributes\PathParameter;

class MeterConsumptionController extends Controller {
    public function __construct(
        private MeterService $meterService,
        private MeterConsumptionService $meterConsumptionService,
    ) {}

    /**
     * List meter consumptions.
     *
     * If the meter has the ability to send data to your server. That is the endpoint where you get the
     * meter readings ( used energy, credit on meter etc.).
     */
    #[PathParameter('start', description: 'Start date in YYYY-mm-dd format.', format: 'date')]
    #[PathParameter('end', description: 'End date in YYYY-mm-dd format.', format: 'date')]
    public function show(string $serialNumber, string $start, string $end): ApiResource {
        $meter = $this->meterService->getBySerialNumber($serialNumber);

        return ApiResource::make($this->meterConsumptionService->getByMeter($meter, $start, $end));
    }
}
