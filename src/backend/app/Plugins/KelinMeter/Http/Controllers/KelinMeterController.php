<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\KelinMeterCollection;
use App\Plugins\KelinMeter\Http\Resources\KelinMeterResource;
use App\Plugins\KelinMeter\Http\Resources\KelinResource;
use App\Plugins\KelinMeter\Services\KelinMeterService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
