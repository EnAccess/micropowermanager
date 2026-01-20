<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaSyncSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SteamaSyncSettingController extends Controller {
    public function __construct(private SteamaSyncSettingService $syncSettingService) {}

    public function update(Request $request): SteamaResource {
        return new SteamaResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
