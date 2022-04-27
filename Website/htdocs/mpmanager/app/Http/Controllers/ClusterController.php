<?php


namespace App\Http\Controllers;


use App\Http\Requests\ClusterRequest;
use App\Http\Resources\ApiResource;
use App\Services\ClusterMeterService;
use App\Services\ClusterMiniGridService;
use App\Services\ClusterPopulationService;
use App\Services\ClusterRevenueService;
use App\Services\ClustersDashboardCacheDataService;
use App\Services\ClusterService;
use App\Models\Cluster;
use App\Services\ClusterTransactionService;
use App\Services\ConnectionTypeService;
use App\Services\MeterRevenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;


class ClusterController extends Controller
{

    public function __construct(
        private ClusterService $clusterService,
        private ClusterMeterService $clusterMetersService,
        private ClusterTransactionService $clusterTransactionsService,
        private ClusterPopulationService $clusterPopulationService,
        private ClusterRevenueService $clusterRevenueService,
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService,
        private ClusterMiniGridService $clusterMiniGridService,
        private ConnectionTypeService $connectionTypeService
    ) {

    }

    /**
     * @throws \Exception
     */
    public function index(Request $request): ApiResource
    {

        $dateRange =
            $this->clusterService->getDateRangeFromRequest($request->get('start_date'), $request->get('end_date'));
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
                    $connectionTypes);
            $clusters[$index]->clusterData =
                $this->clusterService->getCluster(
                    $this->clusterService->getById($cluster->id),
                    $clusters[$index]->meterCount,
                    $clusters[$index]->revenue,
                    $clusters[$index]->population
                );
        }

        return ApiResource::make($clusters);
    }

    /**
     * @throws \Exception
     */
    public function show($clusterId, Request $request): ApiResource
    {
        $dateRange =
            $this->clusterService->getDateRangeFromRequest($request->get('start_date'), $request->get('end_date'));
        $cluster = $this->clusterService->getById($clusterId);
        return ApiResource::make($this->clusterService->getCluster(
            $cluster,
            $this->clusterMetersService->getCountById($cluster->id),
            $this->clusterTransactionsService->getById($cluster->id, $dateRange),
            $this->clusterPopulationService->getById($cluster->id)
        ));
    }

    public function showGeo($clusterId): ApiResource
    {
        return ApiResource::make(['geo_data' => $this->clusterService->getGeoLocationById($clusterId)]);
    }

    public function store(ClusterRequest $request): ApiResource
    {
        $clusterData = $request->only(['name', 'manager_id', 'geo_data']);
        $cluster = $this->clusterService->createCluster($clusterData);
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
            $clusters[$index]->revenueAnalysis = $this->clusterRevenueService->getMonthlyRevenueAnalysisForConnectionTypesById($cluster->id,
                $connectionTypes);
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

        return ApiResource::make($cluster);
    }
}
