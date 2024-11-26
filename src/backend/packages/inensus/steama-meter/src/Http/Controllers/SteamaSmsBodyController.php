<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSmsBodyService;

class SteamaSmsBodyController extends Controller {
    private $smsBodyService;

    public function __construct(SteamaSmsBodyService $smsBodyService) {
        $this->smsBodyService = $smsBodyService;
    }

    public function index(): SteamaResource {
        return new SteamaResource($this->smsBodyService->getSmsBodies());
    }

    public function update(Request $request): SteamaResource {
        return new SteamaResource($this->smsBodyService->updateSmsBodies($request->all()));
    }
}
