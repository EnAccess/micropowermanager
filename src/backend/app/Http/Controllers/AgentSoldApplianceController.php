<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentSoldApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\AgentSoldApplianceService;
use Illuminate\Http\Request;

class AgentSoldApplianceController extends Controller {
    public const FOR_APP = true;

    public function __construct(
        private AgentSoldApplianceService $agentSoldApplianceService,
        private AgentService $agentService,
    ) {}

    public function index(Request $request) {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentSoldApplianceService->getAll($limit, $agent->id, null, self::FOR_APP));
    }

    public function show($customerId, Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentSoldApplianceService->getById($agent->id, $customerId));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateAgentSoldApplianceRequest $request
     *
     * @return ApiResource
     */
    public function store(CreateAgentSoldApplianceRequest $request) {
        $soldApplianceData = $request->only(['person_id', 'agent_assigned_appliance_id']);

        return ApiResource::make($this->agentSoldApplianceService->create($soldApplianceData));
    }
}
