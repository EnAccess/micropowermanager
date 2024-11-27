<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSettingService;

class SteamaSettingController extends Controller {
    private $settingService;

    public function __construct(SteamaSettingService $settingService) {
        $this->settingService = $settingService;
    }

    public function index(): SteamaResource {
        return new SteamaResource($this->settingService->getSettings());
    }
}
