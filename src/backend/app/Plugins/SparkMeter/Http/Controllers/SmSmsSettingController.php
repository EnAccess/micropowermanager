<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\SmSmsSettingService;
use Illuminate\Http\Request;

class SmSmsSettingController {
    public function __construct(private SmSmsSettingService $smsSettingService) {}

    public function update(Request $request): SparkResource {
        return new SparkResource($this->smsSettingService->updateSmsSettings($request->all()));
    }
}
