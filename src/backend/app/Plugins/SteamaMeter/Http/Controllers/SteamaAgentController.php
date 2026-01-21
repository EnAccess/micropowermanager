<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaAgentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SteamaAgentController extends Controller implements IBaseController {
    public function __construct(private SteamaAgentService $agentService) {}

    public function index(Request $request): SteamaResource {
        $customers = $this->agentService->getAgents($request);

        return new SteamaResource($customers);
    }

    public function sync(): SteamaResource {
        return new SteamaResource($this->agentService->sync());
    }

    public function checkSync(): SteamaResource {
        return new SteamaResource($this->agentService->syncCheck());
    }

    public function count(): int {
        return $this->agentService->getAgentsCount();
    }
}
