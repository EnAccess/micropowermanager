<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSettingService;

class SmSettingController extends Controller {
    private $settingService;

    public function __construct(SmSettingService $settingService) {
        $this->settingService = $settingService;
    }

    public function index(): SparkResource {
        return new SparkResource($this->settingService->getSettings());
    }
}
