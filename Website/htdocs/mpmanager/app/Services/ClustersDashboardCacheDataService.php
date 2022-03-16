<?php

namespace App\Services;

use App\Http\Services\ClusterService;
use App\Http\Services\PeriodService;
use App\Http\Services\RevenueService;
use Illuminate\Support\Facades\Cache;
use Nette\Utils\DateTime;

class ClustersDashboardCacheDataService
{
    private $clusterService;
    private $periodService;
    private $revenueService;
    public function __construct(
        ClusterService $clusterService,
        PeriodService $periodService,
        RevenueService $revenueService
    ) {
        $this->clusterService = $clusterService;
        $this->periodService = $periodService;
        $this->revenueService = $revenueService;
    }
    public function setClustersData()
    {
        $this->setClustersListData('ClustersList');
        $this->setClustersRevenues('ClustersRevenue');
    }
    public function setClustersListData($key)
    {
        $dateRange = [];
        $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
        $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));

        $clusters = $this->clusterService->getClusterList();
        $data = $this->clusterService->fetchClusterData($clusters, $dateRange);
        Cache::forever($key, $data);
    }
    public function setClustersRevenues($key)
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
        //get meters in clusters
        $clusters = $this->clusterService->getClusterList(true);
        //generate initial dataset for revenue
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);
        //generate initial dataset for revenue
        foreach ($clusters as $clusterIndex => $cluster) {
            $totalRevenue = 0;
            $p = $periods;
            $revenues = $this->revenueService->clustersRevenueByPeriod(
                $cluster->id,
                [$startDate, $endDate],
                $period
            );


            foreach ($revenues as $rIndex => $revenue) {
                if ($period === 'weekMonth') {
                    $p[$revenue->period][$revenue->week]['revenue'] = $revenue->revenue;
                } elseif ($period = "monthly") {
                    $p[$revenue->period]['revenue'] += $revenue->revenue;
                }
                $totalRevenue += $revenue->revenue;
            }

            $clusters[$clusterIndex]['period'] = $p;
            $clusters[$clusterIndex]['totalRevenue'] = $totalRevenue;

            Cache::forever($key, $clusters);
        }
    }

    public function getData()
    {
        $data['clustersList'] = Cache::get('ClustersList') ? Cache::get('ClustersList') : [];
        $data['clustersRevenue'] = Cache::get('ClustersRevenue') ? Cache::get('ClustersRevenue') : [];
        return $data;
    }
}
