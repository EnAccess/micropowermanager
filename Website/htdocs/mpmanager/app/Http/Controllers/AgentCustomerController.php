<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentCustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentCustomerController extends Controller
{

    private $agentCustomerService;

    public function __construct(AgentCustomerService $agentCustomerService)
    {
        $this->agentCustomerService = $agentCustomerService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request $request
     * @return ApiResource
     */
    public function index(Request $request)
    {
        $agent = Agent::find(auth('agent_api')->user()->id);
        return new ApiResource($this->agentCustomerService->list($agent));
    }
    public function search(): ApiResource
    {
        $term = request('term');
        $paginate = request('paginate') ?? 1;
        $agent = Agent::find(auth('agent_api')->user()->id);
        return new ApiResource($this->agentCustomerService->searchAgentsCustomers($term, $paginate, $agent));
    }
}
