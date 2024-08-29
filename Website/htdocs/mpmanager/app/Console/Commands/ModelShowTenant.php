<?php

namespace App\Console\Commands;

use App\Utils\DummyCompany;
use Illuminate\Console\Command;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class ModelShowTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'model:show-tenant';
    protected $signature = 'model:show-tenant {model : The model to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about an Eloquent model on provided tenant database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionByCompanyId(DummyCompany::DUMMY_COMPANY_ID);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('model:show', [
            'model' => $this->argument('model'),
        ]);
    }
}
