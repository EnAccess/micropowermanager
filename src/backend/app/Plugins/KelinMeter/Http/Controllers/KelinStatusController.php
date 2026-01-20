<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\KelinMeterStatusResource;
use App\Plugins\KelinMeter\Http\Resources\KelinResource;
use App\Plugins\KelinMeter\Models\KelinMeter;
use App\Plugins\KelinMeter\Services\KelinMeterStatusService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KelinStatusController extends Controller {
    public function __construct(private KelinMeterStatusService $kelinMeterStatusService) {}

    public function show(KelinMeter $meter): KelinMeterStatusResource {
        return new KelinMeterStatusResource($this->kelinMeterStatusService->getStatusOfMeter($meter));
    }

    public function update(Request $request, KelinMeter $meter): KelinResource {
        return new KelinResource($this->kelinMeterStatusService->changeStatusOfMeter($meter->meter_address, $request->input('status')));
    }
}
