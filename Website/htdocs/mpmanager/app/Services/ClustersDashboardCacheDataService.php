<?php

namespace App\Services;

use App\Models\Cluster;
use Illuminate\Support\Facades\Cache;
use Nette\Utils\DateTime;

class ClustersDashboardCacheDataService
{
    private const CACHE_KEY_CLUSTER_LIST = 'ClustersList';
    private const CACHE_KEY_CLUSTERS_REVENUE = 'ClustersRevenue';

    public function __construct(
        private PeriodService $periodService,
        private ClusterService $clusterService,
    ) {
    }

    public function setClustersData($clusterData, $clustersWithMeters, $clusterRevenueService)
    {
        $this->setClustersListData(self::CACHE_KEY_CLUSTER_LIST, $clusterData);
        $this->setClustersRevenues(self::CACHE_KEY_CLUSTERS_REVENUE, $clustersWithMeters, $clusterRevenueService);
    }

    private function setClustersListData($key, $clusterData)
    {
        Cache::forever($key, $clusterData);
    }

    public function setClustersRevenues($key, $clustersWithMeters, $clusterRevenueService)
    {
        $startDate = null;
        if (!$startDate) {
            $start = new DateTime();
            $start->setDate($start->format('Y'), $start->format('n'), 1); // Normalize the day to 1
            $start->setTime(0, 0, 0); // Normalize time to midnight
            $start->sub(new \DateInterval('P12M'));
            $startDate = $start->format('Y-m-d');
        }
        $endDate = date('Y-m-t');
        $period = 'monthly';
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);

        //generate initial dataset for revenue
        foreach ($clustersWithMeters as $clusterIndex => $cluster) {
            $totalRevenue = 0;
            $p = $periods;
            $revenues = $clusterRevenueService->getTransactionsForMonthlyPeriodById($cluster->id, [$startDate, $endDate]);

            foreach ($revenues as $rIndex => $revenue) {
                $p[$revenue->period]['revenue'] += $revenue->revenue;
                $totalRevenue += $revenue->revenue;
            }

            $clusters[$clusterIndex]['period'] = $p;
            $clusters[$clusterIndex]['totalRevenue'] = $totalRevenue;

            Cache::forever($key, $clusters);
        }
    }

    public function getData(): array
    {
        $data['clustersList'] = Cache::get('ClustersList') ??  $this->buildCachedList();
        $data['clustersRevenue'] = Cache::get('ClustersRevenue') ?? [];
        return $data;
    }

    public function getDataById($clusterId)
    {
        return Cache::get('ClustersList') ? collect(Cache::get('ClustersList'))->filter(function ($cluster) use ($clusterId) {
            return $cluster['id'] == $clusterId;
        })->first() : [];
    }

    public function buildCachedList()
    {
        $clusters = $this->clusterService->getAll();
        $this->setClustersListData(self::CACHE_KEY_CLUSTER_LIST, $clusters);
        return $clusters->toArray();
    }
}
