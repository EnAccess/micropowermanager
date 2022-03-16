<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentAssignedApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentAssignedApplianceService;
use Illuminate\Http\Request;

/**
 * @group   Agent-Appliances
 * Class AgentAssignedApplianceController
 * @package App\Http\Controllers
 */
class AgentAssignedAppliancesController extends Controller
{

    private $agentAssignedApplianceService;

    public function __construct(AgentAssignedApplianceService $AgentAssignedApplianceService)
    {
        $this->agentAssignedApplianceService = $AgentAssignedApplianceService;
    }

    /**
     * List for Android-APP.
     *
     * @param  Request $request
     * @return ApiResource
     */
    public function index(Request $request)
    {
        $agent = Agent::find(auth('agent_api')->user()->id);
        $assignedAppliances = $this->agentAssignedApplianceService->list($agent->id);
        return new ApiResource($assignedAppliances);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @bodyParam agent_id integer required
     * @bodyParam user_id integer required
     * @bodyParam appliance_type_id integer required
     * @bodyParam cost integer required
     * @param     CreateAgentAssignedApplianceRequest $request
     * @return    ApiResource
     */
    public function store(CreateAgentAssignedApplianceRequest $request)
    {

        $appliance = $this->agentAssignedApplianceService->create(
            $request->only(
                [
                'agent_id',
                'user_id',
                'appliance_type_id',
                'cost',
                ]
            )
        );

        return new ApiResource($appliance);
    }




    /**
     * List for Web interface.
     *
     * @param  Agent   $agent
     * @param  Request $request
     * @return ApiResource
     */
    public function indexWeb(Agent $agent, Request $request)
    {

        $assignedAppliances = $this->agentAssignedApplianceService->list($agent->id);
        return new ApiResource($assignedAppliances);
    }
}
