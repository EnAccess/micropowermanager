<?php

namespace App\Http\Controllers;

use App\DTO\MiniGridDashboardData;
use App\Http\Resources\ApiResource;
use App\Services\MiniGridDashboardCacheDataService;
use Illuminate\Http\Request;

class MiniGridDashboardCacheController extends Controller {
    public function __construct(private MiniGridDashboardCacheDataService $miniGridDashboardCacheDataService) {}

    public function index(): ApiResource {
        /** @var array<MiniGridDashboardData> $cachedData */
        $cachedData = $this->miniGridDashboardCacheDataService->getData();
        $serializedData = array_map(fn (MiniGridDashboardData $dto): array => $dto->toArray(), $cachedData);

        return ApiResource::make($serializedData);
    }

    public function show(int $miniGridId): ApiResource {
        /** @var array<MiniGridDashboardData> $cachedData */
        $cachedData = $this->miniGridDashboardCacheDataService->getDataById($miniGridId);
        $serializedData = array_map(fn (MiniGridDashboardData $dto): array => $dto->toArray(), $cachedData);

        return ApiResource::make($serializedData);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(Request $request): array {
        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        if ($toDate && $fromDate) {
            $this->miniGridDashboardCacheDataService->setData([$fromDate, $toDate]);
        } else {
            $this->miniGridDashboardCacheDataService->setData();
        }

        /** @var array<MiniGridDashboardData> $cachedData */
        $cachedData = $this->miniGridDashboardCacheDataService->getData();
        $serializedData = array_map(fn (MiniGridDashboardData $dto): array => $dto->toArray(), $cachedData);

        return ['data' => $serializedData];
    }
}
