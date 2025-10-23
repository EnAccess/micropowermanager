<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSmsSettingService;

class SmSmsSettingController {
    public function __construct(private SmSmsSettingService $smsSettingService) {}

    public function update(Request $request): SparkResource {
        return new SparkResource($this->smsSettingService->updateSmsSettings($request->all()));
    }
}
