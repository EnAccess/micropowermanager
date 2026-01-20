<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaSettingService;
use Illuminate\Routing\Controller;

class SteamaSettingController extends Controller {
    public function __construct(private SteamaSettingService $settingService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->settingService->getSettings());
    }
}
