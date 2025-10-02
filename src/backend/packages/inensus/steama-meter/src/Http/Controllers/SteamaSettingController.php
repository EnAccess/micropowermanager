<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSettingService;

class SteamaSettingController extends Controller {
    public function __construct(private SteamaSettingService $settingService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->settingService->getSettings());
    }
}
