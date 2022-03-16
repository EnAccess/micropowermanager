<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ClustersDashboardCacheDataService;
use Illuminate\Support\Facades\Artisan;

class ClustersDashboardCacheDataController extends Controller
{
    private $clustersDashboardCacheDataService;


    public function __construct(
        ClustersDashboardCacheDataService $clustersDashboardCacheDataService
    ) {
        $this->clustersDashboardCacheDataService = $clustersDashboardCacheDataService;
    }

    public function index()
    {
        return new ApiResource($this->clustersDashboardCacheDataService->getData());
    }

    public function update()
    {
        Artisan::call('update:cachedClustersDashboardData');
        return new ApiResource($this->clustersDashboardCacheDataService->getData());
    }
}
