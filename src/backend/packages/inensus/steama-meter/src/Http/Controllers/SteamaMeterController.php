<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaMeterService;

class SteamaMeterController extends Controller implements IBaseController {
    private $meterService;

    public function __construct(SteamaMeterService $meterService) {
        $this->meterService = $meterService;
    }

    public function index(Request $request): SteamaResource {
        $customers = $this->meterService->getMeters($request);

        return new SteamaResource($customers);
    }

    public function sync(): SteamaResource {
        return new SteamaResource($this->meterService->sync());
    }

    public function checkSync(): SteamaResource {
        return new SteamaResource($this->meterService->syncCheck());
    }

    public function count() {
        return $this->meterService->getMetersCount();
    }
}
