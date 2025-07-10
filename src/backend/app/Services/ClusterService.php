<?php

namespace App\Services;

use App\Models\Cluster;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Cluster>
 */
class ClusterService implements IBaseService {
    public function __construct(
        private Cluster $cluster,
    ) {}

    protected function setClusterMeterCount(Cluster $cluster, int $meterCount): void {
        $cluster->meterCount = $meterCount;
    }

    protected function setRevenue(Cluster $cluster, float $totalTransactionsAmount): void {
        $cluster->revenue = $totalTransactionsAmount;
    }

    protected function setPopulation(Cluster $cluster, int $populationCount): void {
        $cluster->population = $populationCount;
    }

    public function getCluster(
        Cluster $cluster,
        int $meterCount,
        float $totalTransactionsAmount,
        int $populationCount,
    ): Cluster {
        $this->setClusterMeterCount($cluster, $meterCount);
        $this->setRevenue($cluster, $totalTransactionsAmount);
        $this->setPopulation($cluster, $populationCount);

        return $cluster;
    }

    public function getClusterCities(int $clusterId): ?Cluster {
        return Cluster::query()->with('cities')->find($clusterId);
    }

    public function getClusterMiniGrids(int $clusterId): ?Cluster {
        return Cluster::query()->with('miniGrids')->find($clusterId);
    }

    public function getGeoLocationById(int $clusterId): mixed {
        return $this->cluster->newQuery()->select('geo_data')->find($clusterId)->geo_data;
    }

    /**
     * @param string|null $startDate
     * @param string|null $endDate
     *
     * @return array<int, string>
     */
    public function getDateRangeFromRequest(?string $startDate, ?string $endDate): array {
        $dateRange = [];

        if ($startDate !== null && $endDate !== null) {
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
            $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));
        }

        return $dateRange;
    }

    public function getById(int $clusterId): Cluster {
        return $this->cluster->newQuery()->with(['miniGrids.location', 'cities'])->find($clusterId);
    }

    /**
     * @param array<string, mixed> $clusterData
     */
    public function create(array $clusterData): Cluster {
        return $this->cluster->newQuery()->create($clusterData);
    }

    /**
     * @return Collection<int, Cluster>|LengthAwarePaginator<Cluster>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit !== null) {
            return $this->cluster->newQuery()->with('miniGrids')->limit($limit)->get();
        }

        return $this->cluster->newQuery()->with('miniGrids')->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): Cluster {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
