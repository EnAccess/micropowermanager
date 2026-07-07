<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentAssignedApplianceService;
use App\Services\AgentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('AgentApp', weight: 21)]
class AgentAssignedAppliancesController extends Controller {
    public function __construct(
        private AgentAssignedApplianceService $agentAssignedApplianceService,
        private AgentService $agentService,
    ) {}

    /**
     * List appliances assigned to the authenticated agent.
     *
     * @return ApiResource
     */
    public function index(Request $request) {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentAssignedApplianceService->getAll($limit, $agent->id));
    }
}
