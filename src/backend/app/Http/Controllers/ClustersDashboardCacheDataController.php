<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ClustersDashboardCacheDataService;
use Illuminate\Http\Request;

class ClustersDashboardCacheDataController extends Controller {
    public function __construct(
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService,
    ) {}

    public function index() {
        $cachedData = $this->clustersDashboardCacheDataService->getData();

        // If cache is empty, initialize it before returning
        if (empty($cachedData)) {
            $this->clustersDashboardCacheDataService->setData();
            $cachedData = $this->clustersDashboardCacheDataService->getData();
        }

        return ApiResource::make($cachedData);
    }

    public function show($clusterId) {
        return ApiResource::make($this->clustersDashboardCacheDataService->getDataById($clusterId));
    }

    public function update(Request $request) {
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
