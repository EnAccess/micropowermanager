<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentAssignedApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentAssignedApplianceService;
use Illuminate\Http\Request;

class AgentAssignedApplianceWebController extends Controller {
    public function __construct(
        private AgentAssignedApplianceService $agentAssignedApplianceService,
    ) {}

    /**
     * Assign an appliance to an agent.
     *
     * @return ApiResource
     */
    public function store(CreateAgentAssignedApplianceRequest $request) {
        $assignedApplianceData = $request->only([
            'agent_id',
            'user_id',
            'appliance_id',
            'cost',
        ]);

        return ApiResource::make($this->agentAssignedApplianceService->create($assignedApplianceData));
    }

    /**
     * List appliances assigned to an agent.
     *
     * @param int $agentId
     *
     * @return ApiResource
     */
    public function index(?int $agentId, Request $request) {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentAssignedApplianceService->getAll($limit, $agentId));
    }
}
