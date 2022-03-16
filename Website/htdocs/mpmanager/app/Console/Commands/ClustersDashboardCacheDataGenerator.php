<?php

namespace App\Console\Commands;

use App\Services\ClustersDashboardCacheDataService;
use Illuminate\Console\Command;

class ClustersDashboardCacheDataGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:cachedClustersDashboardData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Clusters Dashboard Data';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $clustersDashboardCacheDataService;


    public function __construct(
        ClustersDashboardCacheDataService $clustersDashboardCacheDataService
    ) {
        parent::__construct();
        $this->clustersDashboardCacheDataService = $clustersDashboardCacheDataService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->clustersDashboardCacheDataService->setClustersData();
    }
}
