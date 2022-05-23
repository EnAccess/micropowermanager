<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentTransactionService;
use Illuminate\Http\Request;

class AgentTransactionWebController extends Controller
{

    public function __construct(private AgentTransactionService $agentTransactionService)
    {

    }

    public function index($agentId, Request $request): ApiResource
    {
        $limit = $request->input('limit');

        return  ApiResource::make($this->agentTransactionService->getAll($limit, $agentId));
    }
}
