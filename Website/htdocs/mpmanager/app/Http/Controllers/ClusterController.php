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
    ) {
    }

    /**
     * @throws \Exception
     */
    public function index(Request $request): ApiResource
    {
        return ApiResource::make($this->clusterService->getAll());
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
        return ApiResource::make($this->clusterService->create($clusterData));
    }
}
