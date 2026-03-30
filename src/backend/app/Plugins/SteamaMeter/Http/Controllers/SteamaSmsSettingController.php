<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaSmsSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SteamaSmsSettingController extends Controller {
    public function __construct(private SteamaSmsSettingService $smsSettingService) {}

    public function update(Request $request): SteamaResource {
        return new SteamaResource($this->smsSettingService->updateSmsSettings($request->all()));
    }
}
