<?php

namespace App\Console\Commands;

use App\Services\DatabaseProxyManagerService;
use Illuminate\Console\Command;

class ModelShowTenant extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'model:show-tenant {model : The model to show}';

    /**
     * The console command description.
     */
    protected $description = 'Show information about an Eloquent model on provided tenant database. This command requires demo data to be loaded.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void {
        $this->databaseProxyManagerService->buildDatabaseConnectionDemoCompany();

        $this->call('model:show', [
            'model' => $this->argument('model'),
        ]);
    }
}
