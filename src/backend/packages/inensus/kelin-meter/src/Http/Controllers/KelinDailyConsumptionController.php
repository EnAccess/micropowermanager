<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\DailyConsumptionResource;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Services\DailyConsumptionService;

class KelinDailyConsumptionController extends Controller {
    private $dailyConsumptionService;

    public function __construct(DailyConsumptionService $dailyConsumptionService) {
        $this->dailyConsumptionService = $dailyConsumptionService;
    }

    public function index(KelinMeter $meter) {
        $perPage = \request()->get('per_page') ?? 15;

        return DailyConsumptionResource::collection(
            $this->dailyConsumptionService->getDailyData($meter->meter_address, $perPage)
        );
    }
}
