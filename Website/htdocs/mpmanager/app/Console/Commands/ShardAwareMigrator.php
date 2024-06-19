<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class ShardAwareMigrator extends Command
{
    protected $description = 'Runs artisan:migrate based on provided company_id';
    protected $signature = 'shard:migrate {company_id} {--force}';

    public function handle()
    {
        $force = $this->option('force');
        $companyId = $this->argument('company_id');

        /** @var DatabaseProxyManagerService $databaseProxyManagerService */
        $databaseProxyManagerService = app()->make(DatabaseProxyManagerService::class);

        $databaseProxyManagerService->runForCompany($companyId, function () use ($force) {
            $command = 'migrate'.($force ? ':fresh' : '');
            $this->info('Calling '.$command);

            $this->call($command);
        });
    }
}
