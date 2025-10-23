<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSyncSettingService;

class SmSyncSettingController {
    public function __construct(private SmSyncSettingService $syncSettingService) {}

    public function update(Request $request): SparkResource {
        return new SparkResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
