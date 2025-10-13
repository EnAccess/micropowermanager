<?php

namespace Inensus\Prospect\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\Prospect\Http\Resources\ProspectResource;
use Inensus\Prospect\Services\ProspectSyncSettingService;

class ProspectSyncSettingController extends Controller {
    public function __construct(private ProspectSyncSettingService $syncSettingService) {}

    public function update(Request $request): ProspectResource {
        return new ProspectResource($this->syncSettingService->updateSyncSettings($request->all()));
    }
}


