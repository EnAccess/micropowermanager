<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentPerformanceMetricsService;
use Illuminate\Http\Request;

class AgentPerformanceMetricsController extends Controller {
    public function __construct(private AgentPerformanceMetricsService $agentMetricsService) {}

    public function index(Request $request): ApiResource {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $metrics = $this->agentMetricsService->getMetrics($startDate, $endDate);

        return ApiResource::make($metrics);
    }
}
