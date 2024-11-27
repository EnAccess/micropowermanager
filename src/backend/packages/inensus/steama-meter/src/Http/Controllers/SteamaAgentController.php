<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaAgentService;

class SteamaAgentController extends Controller implements IBaseController {
    private $agentService;

    public function __construct(SteamaAgentService $agentService) {
        $this->agentService = $agentService;
    }

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

    public function count() {
        return $this->agentService->getAgentsCount();
    }
}
