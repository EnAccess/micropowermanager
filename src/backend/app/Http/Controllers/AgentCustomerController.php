<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentCustomerService;
use App\Services\AgentService;
use Illuminate\Http\Request;

class AgentCustomerController extends Controller {
    public function __construct(
        private AgentCustomerService $agentCustomerService,
        private AgentService $agentService,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function index(Request $request) {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentCustomerService->list($agent));
    }

    public function search(Request $request): ApiResource {
        $term = $request->input('term');
        $limit = $request->input('paginate', 15);
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentCustomerService->search($term, $limit, $agent));
    }
}
