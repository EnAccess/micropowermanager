<?php

namespace App\Console\Commands;

class MigrateTenantRollback extends AbstractSharedCommand
{
    protected $signature = 'migrate-tenant:rollback {--company-id=}';
    protected $description = 'Drop all tables and re-run all migrations on provided tenant database(s)';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate:rollback', [
            '--database' => 'shard',
            '--path' => '/database/migrations/micropowermanager',
        ]);
    }
}
