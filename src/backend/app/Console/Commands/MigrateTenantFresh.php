<?php

namespace App\Console\Commands;

class MigrateTenantFresh extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant:fresh {--company-id=}';
    protected $description = 'Drop all tables and re-run all migrations on provided tenant database(s)';

    public function handle() {
        $this->call('optimize:clear');
        $this->call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
        ]);
    }
}
