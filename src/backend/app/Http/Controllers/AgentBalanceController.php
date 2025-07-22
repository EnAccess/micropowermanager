<?php

namespace App\Http\Controllers;

use App\Services\AgentService;
use Illuminate\Http\Request;

class AgentBalanceController extends Controller {
    public function __construct(
        private AgentService $agentService,
    ) {}

    public function show(Request $request): float {
        $agent = $this->agentService->getByAuthenticatedUser();

        return $this->agentService->getAgentBalance($agent);
    }
}
