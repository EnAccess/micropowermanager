<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentPerformanceMetricsRequest;
use App\Http\Resources\AgentPerformanceMetricsResource;
use App\Services\AgentPerformanceMetricsService;

class AgentPerformanceMetricsController extends Controller {
    public function __construct(private AgentPerformanceMetricsService $agentMetricsService) {}

    public function index(AgentPerformanceMetricsRequest $request): AgentPerformanceMetricsResource {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $period = $request->query('period');
        $metrics = $this->agentMetricsService->getMetrics($startDate, $endDate, $period);

        return AgentPerformanceMetricsResource::make($metrics);
    }
}
