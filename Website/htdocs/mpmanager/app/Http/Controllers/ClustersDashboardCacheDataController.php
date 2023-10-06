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
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService,
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
        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        if ($toDate && $fromDate) {
            $this->clustersDashboardCacheDataService->setData([$fromDate, $toDate]);
        } else {
            $this->clustersDashboardCacheDataService->setData();
        }

        return ['data' => $this->clustersDashboardCacheDataService->getData()];
    }
}
