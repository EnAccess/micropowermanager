<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentAssignedApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Models\AgentAssignedAppliances;
use App\Services\AgentAssignedApplianceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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

    /**
     * Remove an appliance assignment from an agent.
     */
    public function destroy(int $assignedApplianceId): JsonResponse {
        $assignedAppliance = $this->agentAssignedApplianceService->getById($assignedApplianceId)
            ?? throw new ModelNotFoundException()->setModel(AgentAssignedAppliances::class, [$assignedApplianceId]);

        $this->agentAssignedApplianceService->delete($assignedAppliance);

        return response()->json([
            'message' => 'Assigned appliance removed successfully',
        ]);
    }
}
