<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\SmSettingService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Spark Meter')]
class SmSettingController extends Controller {
    public function __construct(private SmSettingService $settingService) {}

    public function index(): SparkResource {
        return new SparkResource($this->settingService->getSettings());
    }
}
