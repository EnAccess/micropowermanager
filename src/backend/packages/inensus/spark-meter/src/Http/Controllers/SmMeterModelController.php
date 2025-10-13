<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\MeterModelService;

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
