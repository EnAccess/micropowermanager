<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\DailyConsumptionCollection;
use Inensus\KelinMeter\Http\Resources\DailyConsumptionResource;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Services\DailyConsumptionService;

class KelinDailyConsumptionController extends Controller {
    public function __construct(private DailyConsumptionService $dailyConsumptionService) {}

    public function index(KelinMeter $meter): DailyConsumptionCollection {
        $perPage = (int) (\request()->get('per_page') ?? 15);

        return new DailyConsumptionCollection(
            DailyConsumptionResource::collection(
                $this->dailyConsumptionService->getDailyData($meter->meter_address, $perPage)
            )
        );
    }
}
