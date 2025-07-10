<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use MPM\Device\ClusterDeviceService;
use Nette\Utils\DateTime;

class ClustersDashboardCacheDataService extends AbstractDashboardCacheDataService {
    private const CACHE_KEY_CLUSTERS_DATA = 'ClustersData';

    public function __construct(
        private PeriodService $periodService,
        private ClusterService $clusterService,
        private ClusterRevenueService $clusterRevenueService,
        private ClusterMiniGridService $clusterMiniGridService,
        private ConnectionTypeService $connectionTypeService,
        private ClusterTransactionService $clusterTransactionsService,
        private ClusterPopulationService $clusterPopulationService,
        private ClusterDeviceService $clusterDeviceService,
    ) {
        parent::__construct(self::CACHE_KEY_CLUSTERS_DATA);
    }

    /**
     * @param array<string> $dateRange
     */
    public function setData(array $dateRange = []): void {
        if (empty($dateRange)) {
            // Set $startDate to 3 months ago
            $startDate = date('Y-m-d', strtotime('-3 months'));
            $endDate = date('Y-m-d H:i:s', strtotime('today'));
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            list($startDate, $endDate) = $dateRange;
        }

        $monthlyPeriods = $this->periodService->generatePeriodicList($startDate, $endDate, 'monthly', [
            'revenue' => 0,
        ]);
        $weeklyPeriods = $this->periodService->generatePeriodicList($startDate, $endDate, 'weekly', [
            'revenue' => 0,
        ]);
        $clusters = $this->clusterMiniGridService->getClustersWithMiniGrids();
        $connectionTypes = $this->connectionTypeService->getAll();

        foreach ($clusters as $index => $cluster) {
            $devicesInCluster = $this->clusterDeviceService->getByClusterId($cluster->id);
            $clusters[$index]->deviceCount = $devicesInCluster->count();
            $meters = $this->clusterDeviceService->getMetersByClusterId($cluster->id);
            $clusters[$index]->meterCount = $meters->count();
            $clusters[$index]->revenue = $this->clusterTransactionsService->getById($cluster->id, $dateRange);

            $clusters[$index]->population = $this->clusterPopulationService->getById($cluster->id);
            $clusters[$index]->citiesRevenue =
                $this->clusterRevenueService->getMonthlyMiniGridBasedRevenueById($cluster->id);
            $clusters[$index]->revenueAnalysis =
                $this->clusterRevenueService->getMonthlyRevenueAnalysisForConnectionTypesById(
                    $cluster->id,
                    $connectionTypes,
                );
            $clusters[$index]->clusterData =
                $this->clusterService->getCluster(
                    $this->clusterService->getById($cluster->id),
                    $clusters[$index]->meterCount,
                    $clusters[$index]->revenue,
                    $clusters[$index]->population
                );
            $periodicRevenue = $this->clusterRevenueService->getPeriodicRevenueForCluster(
                $cluster,
                $startDate,
                $endDate,
                $monthlyPeriods,
                $weeklyPeriods
            );
            $clusters[$index]->period = $periodicRevenue['period'];
            $clusters[$index]->periodWeekly = $periodicRevenue['periodWeekly'];
            $clusters[$index]->totalRevenue = $periodicRevenue['totalRevenue'];
        }
        Cache::put(self::cacheKeyGenerator(), $clusters, DateTime::from('+ 1 day'));
    }
}
