<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSyncSettingService;

class SteamaSyncSettingController extends Controller {
    private $syncSettingService;

    public function __construct(SteamaSyncSettingService $syncSettingService) {
        $this->syncSettingService = $syncSettingService;
    }

    public function update(Request $request): SteamaResource {
        return new SteamaResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
