<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\KelinResource;
use Inensus\KelinMeter\Services\KelinSyncSettingService;

class KelinSyncSettingController extends Controller {
    private $syncSettingService;

    public function __construct(KelinSyncSettingService $syncSettingService) {
        $this->syncSettingService = $syncSettingService;
    }

    public function update(Request $request): KelinResource {
        return new KelinResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}
