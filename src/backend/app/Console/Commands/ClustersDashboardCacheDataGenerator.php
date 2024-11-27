<?php

namespace App\Console\Commands;

use App\Services\ClustersDashboardCacheDataService;

class ClustersDashboardCacheDataGenerator extends AbstractSharedCommand {
    protected $signature = 'update:cachedClustersDashboardData';
    protected $description = 'Update Clusters Dashboard Data';

    public function __construct(
        private ClustersDashboardCacheDataService $clustersDashboardCacheDataService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->clustersDashboardCacheDataService->setData();
    }
}
