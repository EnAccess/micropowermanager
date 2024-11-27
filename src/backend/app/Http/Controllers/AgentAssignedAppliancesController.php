<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentAssignedApplianceService;
use App\Services\AgentService;
use Illuminate\Http\Request;

/**
 * @group   Agent-Appliances
 * Class AgentAssignedApplianceController
 */
class AgentAssignedAppliancesController extends Controller {
    public function __construct(
        private AgentAssignedApplianceService $agentAssignedApplianceService,
        private AgentService $agentService,
    ) {}

    /**
     * List for Android-APP.
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function index(Request $request) {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentAssignedApplianceService->getAll($limit, $agent->id));
    }
}
