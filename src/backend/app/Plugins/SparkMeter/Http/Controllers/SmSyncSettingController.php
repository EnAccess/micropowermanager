<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\SmSyncSettingService;
use Illuminate\Http\Request;

class SmSyncSettingController {
    public function __construct(private SmSyncSettingService $syncSettingService) {}

    public function update(Request $request): SparkResource {
        return new SparkResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
