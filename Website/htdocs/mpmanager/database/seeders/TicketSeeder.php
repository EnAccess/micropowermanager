<?php

namespace Database\Seeders;

use App\Utils\DummyCompany;
use Illuminate\Database\Seeder;
use Inensus\Ticket\Models\TicketCategory;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class TicketSeeder extends Seeder
{
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionByCompanyId(DummyCompany::DUMMY_COMPANY_ID);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Ticket categories
        TicketCategory::newFactory()
            ->count(2)
            ->sequence(
                [
                    'label_name' => 'Internal',
                    'label_color' => 'lime',
                    'out_source' => 0,
                ],
                [
                    'label_name' => 'Outsource',
                    'label_color' => 'sky',
                    'out_source' => 1,
                ]
            )
            ->create();

        // TODO: Generate actual ticket data
    }
}
