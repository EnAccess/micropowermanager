<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\MinutelyConsumptionResource;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Services\MinutelyConsumptionService;

class KelinMinutelyConsumptionController extends Controller {
    private $minutelyConsumptionService;

    public function __construct(MinutelyConsumptionService $minutelyConsumptionService) {
        $this->minutelyConsumptionService = $minutelyConsumptionService;
    }

    public function index(KelinMeter $meter) {
        $perPage = \request()->get('per_page') ?? 15;

        return MinutelyConsumptionResource::collection($this->minutelyConsumptionService->getDailyData(
            $meter->meter_address,
            $perPage
        ));
    }
}
