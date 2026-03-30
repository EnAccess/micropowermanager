<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\KelinResource;
use App\Plugins\KelinMeter\Services\KelinSyncSettingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KelinSyncSettingController extends Controller {
    public function __construct(private KelinSyncSettingService $syncSettingService) {}

    public function update(Request $request): KelinResource {
        return new KelinResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
