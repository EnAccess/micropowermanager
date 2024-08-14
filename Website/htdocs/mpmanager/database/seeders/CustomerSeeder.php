<?php

namespace Database\Seeders;

use App\Models\Person\Person;
use Illuminate\Database\Seeder;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

const DUMMY_COMPANY_ID = 1;

class CustomerSeeder extends Seeder
{
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionByCompanyId(DUMMY_COMPANY_ID);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Person::factory()->count(50)->isCustomer()->create();
    }
}
