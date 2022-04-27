<?php


namespace App\Services;


use App\Models\City;
use App\Models\Cluster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ClusterService extends BaseService
{
    public function __construct(
        private Cluster $cluster
    ) {
        parent::__construct([$cluster]);

    }

    public function getById(int $clusterId)
    {
        return $this->cluster->newQuery()->with(['miniGrids.location','cities'])->find($clusterId);
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

    public function createCluster($clusterData)
    {
        return $this->cluster->newQuery()->create($clusterData);
    }

    /**
     * @param $clusterId
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function getClusterCities($clusterId)
    {
        return Cluster::query()->with('cities')->find($clusterId);
    }


    /**
     * @param $clusterId
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function getClusterMiniGrids($clusterId)
    {
        return Cluster::query()->with('miniGrids')->find($clusterId);
    }



    public function getClusterList(bool $withCities = false)
    {
        if (!$withCities) {
            return Cluster::query()->get();
        }
        return Cluster::query()->with('miniGrids')->get();
    }

    public function getClustersCities($clusters, $callback): void
    {
        foreach ($clusters as $cluster) {
            $callback($cluster->cities()->get());
        }
    }

    public function attachCities(Cluster $cluster, $cities): void
    {
        foreach ($cities as $cityId) {
            $city = City::query()->find($cityId);
            $cluster->cities()->save($city);
        }
    }

    public function getGeoLocationById($clusterId)
    {
        $cluster = $this->cluster->newQuery()->select('geo_data')->find($clusterId);
        return $cluster->geo_data;
    }

    public function findManagerId(int $clusterId): ?int
    {
        return $this->cluster->where('id', $clusterId)
            ->select('managerId')
            ->first();
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
}
