<?php

namespace App\Services;

use App\DTO\ClusterDashboardData;
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
        if ($dateRange === []) {
            // Set $startDate to 3 months ago
            $startDate = date('Y-m-d', strtotime('-3 months'));
            $endDate = date('Y-m-d H:i:s', strtotime('today'));
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            [$startDate, $endDate] = $dateRange;
        }

        $monthlyPeriods = $this->periodService->generatePeriodicList($startDate, $endDate, 'monthly', [
            'revenue' => 0,
        ]);
        $weeklyPeriods = $this->periodService->generatePeriodicList($startDate, $endDate, 'weekly', [
            'revenue' => 0,
        ]);
        $clusters = $this->clusterMiniGridService->getClustersWithMiniGrids();
        $connectionTypes = $this->connectionTypeService->getAll();

        $clusterDashboardData = [];
        foreach ($clusters as $cluster) {
            $devicesInCluster = $this->clusterDeviceService->getByClusterId($cluster->id);
            $meters = $this->clusterDeviceService->getMetersByClusterId($cluster->id);
            $meterCount = $meters->count();
            $revenue = $this->clusterTransactionsService->getById($cluster->id, $dateRange);
            $population = $this->clusterPopulationService->getById($cluster->id);

            $citiesRevenue = $this->clusterRevenueService->getMonthlyMiniGridBasedRevenueById($cluster->id);
            $revenueAnalysis = $this->clusterRevenueService->getMonthlyRevenueAnalysisForConnectionTypesById(
                $cluster->id,
                $connectionTypes,
            );

            $periodicRevenue = $this->clusterRevenueService->getPeriodicRevenueForCluster(
                $cluster,
                $startDate,
                $endDate,
                $monthlyPeriods,
                $weeklyPeriods
            );

            $clusterDashboardData[] = new ClusterDashboardData(
                cluster: $this->clusterService->getById($cluster->id),
                deviceCount: $devicesInCluster->count(),
                meterCount: $meterCount,
                revenue: $revenue,
                population: $population,
                citiesRevenue: $citiesRevenue,
                revenueAnalysis: $revenueAnalysis,
                period: $periodicRevenue['period'],
                periodWeekly: $periodicRevenue['periodWeekly'],
                totalRevenue: $periodicRevenue['totalRevenue'],
            );
        }
        Cache::put(self::cacheKeyGenerator(), $clusterDashboardData, DateTime::from('+ 1 day'));
    }
}
