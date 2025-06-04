<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentAssignedApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentAssignedApplianceService;
use Illuminate\Http\Request;

class AgentAssignedApplianceWebController extends Controller {
    public function __construct(
        private AgentAssignedApplianceService $agentAssignedApplianceService,
    ) {}

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam agent_id integer required
     * @bodyParam user_id integer required
     * @bodyParam appliance_type_id integer required
     * @bodyParam cost integer required
     *
     * @param CreateAgentAssignedApplianceRequest $request
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
     * List for Web interface.
     *
     * @param int     $agentId
     * @param Request $request
     *
     * @return ApiResource
     */
    public function index($agentId, Request $request) {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentAssignedApplianceService->getAll($limit, $agentId));
    }
}
