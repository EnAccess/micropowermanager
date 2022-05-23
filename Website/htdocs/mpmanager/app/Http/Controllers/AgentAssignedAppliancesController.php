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


    public function __construct(
        private AgentAssignedApplianceService $agentAssignedApplianceService
    ) {

    }

    /**
     * List for Android-APP.
     *
     * @param Request $request
     * @return ApiResource
     */
    public function index(Request $request)
    {
        $agent = Agent::find(auth('agent_api')->user()->id);


        return ApiResource::make($this->agentAssignedApplianceService->listByAgentId($agent->id));
    }


}
