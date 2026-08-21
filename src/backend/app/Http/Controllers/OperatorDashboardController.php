<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Jobs\OperatorDashboardRebuildJob;
use App\Services\OperatorDashboardService;
use Illuminate\Http\JsonResponse;

class OperatorDashboardController extends Controller {
    public function __construct(private OperatorDashboardService $operatorDashboardService) {}

    public function index(): ApiResource {
        return ApiResource::make($this->operatorDashboardService->platformSnapshot()->toArray());
    }

    public function show(int $companyId): ApiResource {
        return ApiResource::make($this->operatorDashboardService->tenantSnapshot($companyId)->toDetailArray());
    }

    /**
     * Queues a rebuild and answers immediately with the freshness stamp the client
     * should poll against. Building inline would fan out across every tenant
     * database inside a web request.
     */
    public function refresh(?int $companyId = null): JsonResponse {
        if (!$this->operatorDashboardService->isRefreshing()) {
            $this->operatorDashboardService->markRefreshing();
            dispatch(new OperatorDashboardRebuildJob($companyId));
        }

        return response()->json([
            'data' => [
                'refreshing' => true,
                'generated_at' => $this->operatorDashboardService->generatedAt()?->toIso8601String(),
            ],
        ], 202);
    }
}
