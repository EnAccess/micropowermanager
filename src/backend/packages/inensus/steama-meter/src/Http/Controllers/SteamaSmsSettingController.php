<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSmsSettingService;

class SteamaSmsSettingController extends Controller {
    private $smsSettingService;

    public function __construct(SteamaSmsSettingService $smsSettingService) {
        $this->smsSettingService = $smsSettingService;
    }

    public function update(Request $request): SteamaResource {
        return new SteamaResource($this->smsSettingService->updateSmsSettings($request->all()));
    }
}
