<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaSmsBodyService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

#[Group('Plugins / Steama Meter')]
class SteamaSmsBodyController extends Controller {
    public function __construct(private SteamaSmsBodyService $smsBodyService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->smsBodyService->getSmsBodies());
    }

    public function update(Request $request): SteamaResource {
        return new SteamaResource($this->smsBodyService->updateSmsBodies($request->all()));
    }
}
