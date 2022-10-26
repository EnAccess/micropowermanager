<?php

namespace App\Console\Commands;

use App\Services\ClustersDashboardCacheDataService;
use Illuminate\Console\Command;

class ClustersDashboardCacheDataGenerator extends AbstractSharedCommand
{
    protected $signature = 'update:cachedClustersDashboardData';
    protected $description = 'Update Clusters Dashboard Data';

    public function __construct(
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService
    ) {
        parent::__construct();
    }

    public function runInCompanyScope(): void
    {
        $this->clustersDashboardCacheDataService->setClustersData();
    }
}
