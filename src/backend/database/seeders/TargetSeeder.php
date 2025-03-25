<?php

namespace Database\Seeders;

use App\Models\ConnectionGroup;
use App\Models\SubTarget;
use App\Models\Target;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;

class TargetSeeder extends Seeder {
    public function __construct(
        private CompanyService $companyService,
    ) {
        $this->companyService->buildDatabaseConnectionDemoCompany();
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
