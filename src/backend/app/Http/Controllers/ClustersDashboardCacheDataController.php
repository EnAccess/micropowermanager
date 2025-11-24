<?php

namespace App\Http\Controllers;

use App\DTO\ClusterDashboardData;
use App\Http\Resources\ApiResource;
use App\Services\ClustersDashboardCacheDataService;
use Illuminate\Http\Request;

class ClustersDashboardCacheDataController extends Controller {
    public function __construct(
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService,
    ) {}

    public function index(): ApiResource {
        /** @var array<ClusterDashboardData> $cachedData */
        $cachedData = $this->clustersDashboardCacheDataService->getData();
        $serializedData = array_map(fn (ClusterDashboardData $dto): array => $dto->toArray(), $cachedData);

        // If cache is empty, initialize it before returning
        if ($cachedData === []) {
            $this->clustersDashboardCacheDataService->setData();
            /** @var array<ClusterDashboardData> $cachedData */
            $cachedData = $this->clustersDashboardCacheDataService->getData();
            $serializedData = array_map(fn (ClusterDashboardData $dto): array => $dto->toArray(), $cachedData);
        }

        return ApiResource::make($serializedData);
    }

    public function show(int $clusterId): ApiResource {
        /** @var array<ClusterDashboardData> $cachedData */
        $cachedData = $this->clustersDashboardCacheDataService->getDataById($clusterId);
        $serializedData = array_map(fn (ClusterDashboardData $dto): array => $dto->toArray(), $cachedData);

        return ApiResource::make($serializedData);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(Request $request): array {
        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        if ($toDate && $fromDate) {
            $this->clustersDashboardCacheDataService->setData([$fromDate, $toDate]);
        } else {
            $this->clustersDashboardCacheDataService->setData();
        }

        /** @var array<ClusterDashboardData> $cachedData */
        $cachedData = $this->clustersDashboardCacheDataService->getData();
        $serializedData = array_map(fn (ClusterDashboardData $dto): array => $dto->toArray(), $cachedData);

        return ['data' => $serializedData];
    }
}
