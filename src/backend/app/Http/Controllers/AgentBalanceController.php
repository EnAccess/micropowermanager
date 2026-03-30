<?php

namespace App\Http\Controllers;

use App\Services\AgentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('AgentApp', weight: 21)]
class AgentBalanceController extends Controller {
    public function __construct(
        private AgentService $agentService,
    ) {}

    public function show(Request $request): float {
        $agent = $this->agentService->getByAuthenticatedUser();

        return $this->agentService->getAgentBalance($agent);
    }
}
