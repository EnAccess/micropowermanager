<?php

namespace App\Plugins\Prospect\Http\Controllers;

use App\Plugins\Prospect\Http\Resources\ProspectSyncSettingResource;
use App\Plugins\Prospect\Services\ProspectSyncSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;

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
