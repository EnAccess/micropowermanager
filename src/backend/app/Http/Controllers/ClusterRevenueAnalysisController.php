<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ClusterRevenueService;
use App\Services\ClusterService;
use App\Services\ConnectionTypeService;
use Illuminate\Http\Request;

class ClusterRevenueAnalysisController extends Controller {
    public function __construct(
        private ClusterService $clusterService,
        private ClusterRevenueService $clusterRevenueService,
        private ConnectionTypeService $connectionTypeService,
    ) {}

    public function show(int $clusterId, Request $request): ApiResource {
        /**
         * !!!!
         * To group revenue by city -> connection type
         * use following structure
         * $revenueAnalysis[$connectionType->name] = $periods;.
         */
        $revenueAnalysis = [];

        $startDate = $request->get('startDate') ?? date('Y-01-01');
        $endDate = $request->get('endDate') ?? date('Y-m-t');
        $period = $request->get('period') ?? 'monthly';
        $cluster = $this->clusterService->getById($clusterId);
        $connectionTypes = $this->connectionTypeService->getAll();

        return ApiResource::make($this->clusterRevenueService->getRevenueAnalysisForConnectionTypesByCluser(
            $startDate,
            $endDate,
            $period,
            $cluster,
            $connectionTypes
        ));
    }
}
