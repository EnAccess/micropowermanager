<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ClusterMeterService;
use App\Services\ClusterMiniGridService;
use App\Services\ClusterPopulationService;
use App\Services\ClusterRevenueService;
use App\Services\ClusterService;
use App\Services\ClusterTransactionService;
use App\Services\PeriodService;
use Illuminate\Http\Request;

class ClusterRevenueController extends Controller {
    public function __construct(
        private ClusterService $clusterService,
        private ClusterRevenueService $clusterRevenueService,
        private ClusterMiniGridService $clusterMiniGridService,
        private ClusterMeterService $clusterMeterService,
        private ClusterTransactionService $clusterTransactionService,
        private ClusterPopulationService $clusterPopulationService,
        private PeriodService $periodService,
    ) {}

    public function index(Request $request): ApiResource {
        $dates =
            $this->clusterRevenueService->setDatesForRequest($request->get('startDate'), $request->get('endDate'));
        $startDate = $dates['startDate'];
        $endDate = $dates['endDate'];
        $period = $request->get('period') ?? 'monthly';
        $clusters = $this->clusterMiniGridService->getClustersWithMiniGrids();
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);

        return ApiResource::make(
            $this->clusterRevenueService->getPeriodicRevenueForClustersOld(
                $clusters,
                $startDate,
                $endDate,
                $periods,
                $period
            )
        );
    }

    public function show(int $clusterId, Request $request): ApiResource {
        $dateRange =
            $this->clusterRevenueService->setDateRangeForRequest($request->get('startDate'), $request->get('endDate'));
        $cluster = $this->clusterService->getById($clusterId);
        $cluster->meterCount = $this->clusterMeterService->getCountById($cluster->id);
        $cluster->revenue = $this->clusterTransactionService->getById($cluster->id, $dateRange);
        $cluster->population = $this->clusterPopulationService->getById($cluster->id);

        return ApiResource::make($cluster);
    }
}
