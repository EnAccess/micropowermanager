<?php

namespace App\Services;

use App\Models\City;
use App\Models\Cluster;
use Illuminate\Database\Eloquent\Collection;

class ClusterService implements IBaseService
{
    public function __construct(
        private Cluster $cluster
    ) {
    }

    protected function setClusterMeterCount(Cluster $cluster, int $meterCount)
    {
        $cluster->meterCount = $meterCount;
    }

    protected function setRevenue(Cluster $cluster, int $totalTransactionsAmount)
    {
        $cluster->revenue = $totalTransactionsAmount;
    }

    protected function setPopulation(Cluster $cluster, int $populationCount)
    {
        $cluster->population = $populationCount;
    }

    public function getCluster(
        Cluster $cluster,
        int $meterCount,
        int $totalTransactionsAmount,
        int $populationCount
    ): Cluster {
        $this->setClusterMeterCount($cluster, $meterCount);
        $this->setRevenue($cluster, $totalTransactionsAmount);
        $this->setPopulation($cluster, $populationCount);
        return $cluster;
    }


    public function getClusterCities($clusterId)
    {
        return Cluster::query()->with('cities')->find($clusterId);
    }

    public function getClusterMiniGrids($clusterId)
    {
        return Cluster::query()->with('miniGrids')->find($clusterId);
    }

    public function getGeoLocationById($clusterId)
    {
        return $this->cluster->newQuery()->select('geo_data')->find($clusterId)->geo_data;
    }

    public function getDateRangeFromRequest($startDate, $endDate): array
    {
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

    public function getById($clusterId): Cluster
    {
        return $this->cluster->newQuery()->with(['miniGrids.location','cities'])->find($clusterId);
    }

    public function create($clusterData)
    {
        return $this->cluster->newQuery()->create($clusterData);
    }

    public function getAll($limit = null): Collection
    {
        if ($limit !== null) {
            return $this->cluster->newQuery()->with('miniGrids')->limit($limit)->get();
        }
        return $this->cluster->newQuery()->with('miniGrids')->get();
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
