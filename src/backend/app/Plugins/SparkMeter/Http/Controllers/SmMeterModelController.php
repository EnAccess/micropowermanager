<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\MeterModelService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SmMeterModelController extends Controller implements IBaseController {
    public function __construct(private MeterModelService $meterModelService) {}

    public function index(Request $request): SparkResource {
        $meterModels = $this->meterModelService->getSmMeterModels($request);

        return new SparkResource($meterModels);
    }

    public function sync(): SparkResource {
        return new SparkResource($this->meterModelService->sync());
    }

    public function checkSync(): SparkResource {
        return new SparkResource($this->meterModelService->syncCheck());
    }

    public function count(): int {
        return $this->meterModelService->getSmMeterModelsCount();
    }
}
