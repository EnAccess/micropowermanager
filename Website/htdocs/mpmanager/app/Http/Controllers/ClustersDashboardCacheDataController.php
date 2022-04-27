<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ClusterMeterService;
use App\Services\ClusterMiniGridService;
use App\Services\ClusterPopulationService;
use App\Services\ClusterRevenueService;
use App\Services\ClustersDashboardCacheDataService;
use App\Services\ClusterService;
use App\Services\ClusterTransactionService;
use App\Services\ConnectionTypeService;
use App\Services\MeterRevenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ClustersDashboardCacheDataController extends Controller
{

    public function __construct(
        private ClusterService $clusterService,
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService,
        private ClusterMeterService $clusterMetersService,
        private ClusterTransactionService $clusterTransactionsService,
        private ClusterPopulationService $clusterPopulationService,
        private ClusterRevenueService $clusterRevenueService,
        private ClusterMiniGridService $clusterMiniGridService,
        private MeterRevenueService $meterRevenueService,
        private ConnectionTypeService $connectionTypeService
    ) {

    }


    public function index()
    {
        return ApiResource::make($this->clustersDashboardCacheDataService->getData());
    }

    public function show($clusterId)
    {
        return ApiResource::make($this->clustersDashboardCacheDataService->getDataById($clusterId));
    }

    public function update(Request $request)
    {
        $dateRange = [];
        $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
        $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));
        $clusters = $this->clusterService->getClusterList();
        $connectionTypes = $this->connectionTypeService->getConnectionTypes();
        foreach ($clusters as $index => $cluster) {
            $clusters[$index]->meterCount = $this->clusterMetersService->getCountById($cluster->id);
            $clusters[$index]->revenue = $this->clusterTransactionsService->getById($cluster->id, $dateRange);
            $clusters[$index]->population = $this->clusterPopulationService->getById($cluster->id);
            $clusters[$index]->citiesRevenue =
                $this->clusterRevenueService->getMonthlyMiniGridBasedRevenueById($cluster->id);
            $clusters[$index]->revenueAnalysis =
                $this->clusterRevenueService->getMonthlyRevenueAnalysisForConnectionTypesById($cluster->id,
                    $connectionTypes, $this->meterRevenueService);
            $clusters[$index]->clusterData =
                $this->clusterService->getCluster(
                    $this->clusterService->getById($cluster->id),
                    $clusters[$index]->meterCount,
                    $clusters[$index]->revenue,
                    $clusters[$index]->population
                );
        }

        $clustersWithMeters = $this->clusterMiniGridService->getClustersWithMiniGrids();
        $this->clustersDashboardCacheDataService->setClustersData($clusters, $clustersWithMeters,$this->clusterRevenueService);

        return ApiResource::make($this->clustersDashboardCacheDataService->getData());
    }
}
