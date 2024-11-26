<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSyncSettingService;

class SmSyncSettingController {
    private $syncSettingService;

    public function __construct(SmSyncSettingService $syncSettingService) {
        $this->syncSettingService = $syncSettingService;
    }

    public function update(Request $request): SparkResource {
        return new SparkResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
