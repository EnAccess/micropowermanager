<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ClusterRevenueService;
use Illuminate\Http\Request;

class ClusterMiniGridRevenueController extends Controller {
    public function __construct(
        private ClusterRevenueService $clusterRevenueService,
    ) {}

    public function show(int $clusterId, Request $request): ApiResource {
        $startDate = $request->input('startDate') ?? date('Y-01-01');
        $endDate = $request->input('endDate') ?? date('Y-m-t');
        $period = $request->input('period') ?? 'monthly';

        return ApiResource::make($this->clusterRevenueService->getMiniGridBasedRevenueById(
            $clusterId,
            $startDate,
            $endDate,
            $period
        ));
    }
}
