<?php

namespace Database\Seeders;

use App\Models\ConnectionGroup;
use App\Models\MiniGrid;
use App\Models\SubTarget;
use App\Models\Target;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Database\Seeder;

class TargetSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $connectionGroups = ConnectionGroup::all();

        // Get available MiniGrids
        $miniGrids = MiniGrid::all();

        // Setting Revenue Targets for each Mini Grids as these are shown on the dashboard
        foreach ($miniGrids as $miniGrid) {
            $current_target = Target::factory()
                ->for($miniGrid, 'owner')
                ->createOne();

            // For each connection group, create a sub-target
            foreach ($connectionGroups as $connectionGroup) {
                SubTarget::factory()
                    ->for($current_target)
                    ->for($connectionGroup, 'connectionType')
                    ->state(['revenue' => 500000])
                    ->createOne();
            }
        }
    }
}
