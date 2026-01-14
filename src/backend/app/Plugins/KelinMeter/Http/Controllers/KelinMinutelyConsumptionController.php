<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\MinutelyConsumptionCollection;
use App\Plugins\KelinMeter\Http\Resources\MinutelyConsumptionResource;
use App\Plugins\KelinMeter\Models\KelinMeter;
use App\Plugins\KelinMeter\Services\MinutelyConsumptionService;
use Illuminate\Routing\Controller;

class KelinMinutelyConsumptionController extends Controller {
    public function __construct(private MinutelyConsumptionService $minutelyConsumptionService) {}

    public function index(KelinMeter $meter): MinutelyConsumptionCollection {
        $perPage = (int) (\request()->get('per_page') ?? 15);

        return new MinutelyConsumptionCollection(
            MinutelyConsumptionResource::collection($this->minutelyConsumptionService->getDailyData(
                $meter->meter_address,
                $perPage
            ))
        );
    }
}
