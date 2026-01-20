<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\DailyConsumptionCollection;
use App\Plugins\KelinMeter\Http\Resources\DailyConsumptionResource;
use App\Plugins\KelinMeter\Models\KelinMeter;
use App\Plugins\KelinMeter\Services\DailyConsumptionService;
use Illuminate\Routing\Controller;

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
