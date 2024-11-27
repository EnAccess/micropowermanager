<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\KelinSettingResource;
use Inensus\KelinMeter\Services\KelinSettingService;

class KelinSettingController extends Controller {
    private $settingService;

    public function __construct(KelinSettingService $settingService) {
        $this->settingService = $settingService;
    }

    public function index() {
        return KelinSettingResource::collection($this->settingService->getSettings());
    }
}
