<?php

namespace Database\Seeders;

use App\Models\ConnectionGroup;
use App\Models\SubTarget;
use App\Models\Target;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

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
        $connections = ConnectionGroup::all();

        $targets = Target::factory()->count(8)->create();

        foreach ($targets as $target) {
            $randomConnection = $connections->random();
            SubTarget::factory()->create(['target_id' => $target->id, 'connection_id' => $randomConnection->id]);
        }
    }
}
