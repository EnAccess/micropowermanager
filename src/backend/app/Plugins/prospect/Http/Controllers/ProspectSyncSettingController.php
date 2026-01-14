<?php

namespace Inensus\Prospect\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Inensus\Prospect\Http\Resources\ProspectSyncSettingResource;
use Inensus\Prospect\Services\ProspectSyncSettingService;

class ProspectSyncSettingController extends Controller {
    public function __construct(private ProspectSyncSettingService $syncSettingService) {}

    public function index(): JsonResource {
        return ProspectSyncSettingResource::collection($this->syncSettingService->getSyncSettings());
    }

    public function update(Request $request): JsonResource {
        $payload = $request->all();

        return ProspectSyncSettingResource::collection($this->syncSettingService->updateSyncSettings($payload));
    }
}
