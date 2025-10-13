<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSettingService;

class SmSettingController extends Controller {
    public function __construct(private SmSettingService $settingService) {}

    public function index(): SparkResource {
        return new SparkResource($this->settingService->getSettings());
    }
}
