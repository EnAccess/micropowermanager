<?php

namespace App\Console\Commands;

class MigrateTenantReset extends AbstractSharedCommand
{
    protected $signature = 'migrate-tenant:reset {--company-id=}';
    protected $description = 'Drop all tables and re-run all migrations on provided tenant database(s)';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate:reset', [
            '--database' => 'shard',
            '--path' => '/database/migrations/micropowermanager',
        ]);
    }
}
