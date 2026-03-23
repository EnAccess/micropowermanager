<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Database\Seeder;

class ApiKeySeeder extends Seeder {
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
        ApiKey::factory()
            ->isDemoApiKey()
            ->createOne();
    }
}
