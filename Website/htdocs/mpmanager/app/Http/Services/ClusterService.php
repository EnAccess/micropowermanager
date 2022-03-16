<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 2019-03-13
 * Time: 19:19
 */

namespace App\Http\Services;

use App\Models\City;
use App\Models\Cluster;
use App\Services\ClusterRevenueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ClusterService
{


    private $meterService;

    private $transactionService;

    private $cityService;

    private $periodService;

    private $revenueService;

    private $clusterRevenueService;
    private Cluster $cluster;

    /**
     * ClusterService constructor.
     *
     * @param MeterService $meterService
     * @param TransactionService $transactionService
     * @param CityService $cityService
     */
    public function __construct(
        MeterService $meterService,
        TransactionService $transactionService,
        CityService $cityService,
        PeriodService $periodService,
        RevenueService $revenueService,
        ClusterRevenueService $clusterRevenueService,
        Cluster $cluster
    ) {
        $this->meterService = $meterService;
        $this->cityService = $cityService;
        $this->transactionService = $transactionService;
        $this->periodService = $periodService;
        $this->revenueService = $revenueService;
        $this->clusterRevenueService = $clusterRevenueService;
        $this->cluster = $cluster;
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
     * @param $id
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function getCluster($id)
    {
        $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
        $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));
        $cluster = Cluster::with('miniGrids.location')->find($id);
        $cluster->meterCount = $this->meterService->getMeterCountInCluster($cluster->id);
        $cluster->revenue = $this->transactionService->totalClusterTransactions($cluster->id, $dateRange);
        $cluster->population = $this->cityService->getClusteropulation($cluster->id);
        return $cluster;
    }

    /**
     * @param $clusterId
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function getClusterMiniGrids($clusterId)
    {
        return Cluster::query()->with('miniGrids')->find($clusterId);
    }

    /**
     * @param (\Illuminate\Http\Request|array|string)[] $range
     * @param array $range
     * @return mixed
     */
    public function fetchClusterData($clusters, array $range = [])
    {
        foreach ($clusters as $index => $cluster) {
            $clusters[$index]->meterCount = $this->meterService->getMeterCountInCluster($cluster->id);
            $clusters[$index]->revenue = $this->transactionService->totalClusterTransactions($cluster->id, $range);
            $clusters[$index]->population = $this->cityService->getClusteropulation($cluster->id);
            $clusters[$index]->citiesRevenue = $this->clusterRevenueService->getMiniGridBasedRevenue($cluster->id);
            $clusters[$index]->revenueAnalysis = $this->clusterRevenueService->getRevenueAnalysis($cluster->id);
            $clusters[$index]->clusterData = $this->getCluster($cluster->id);
        }
        return $clusters;
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

    public function geoLocation($clusterId)
    {
        $cluster = Cluster::select('geo_data')->find($clusterId);
        return $cluster->geo_data;
    }

    public function findManagerId(int $clusterId): ?int
    {
        return  $this->cluster->where('id', $clusterId)
            ->select('managerId')
            ->first();
    }
}
