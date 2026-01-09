<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\KelinMeterCollection;
use Inensus\KelinMeter\Http\Resources\KelinMeterResource;
use Inensus\KelinMeter\Http\Resources\KelinResource;
use Inensus\KelinMeter\Services\KelinMeterService;

class KelinMeterController extends Controller {
    public function __construct(private KelinMeterService $meterService) {}

    public function index(Request $request): KelinMeterCollection {
        return new KelinMeterCollection(KelinMeterResource::collection($this->meterService->getMeters($request)));
    }

    public function sync(): KelinMeterCollection {
        return new KelinMeterCollection(KelinMeterResource::collection($this->meterService->sync()));
    }

    public function checkSync(): KelinResource {
        return new KelinResource($this->meterService->syncCheck());
    }
}
