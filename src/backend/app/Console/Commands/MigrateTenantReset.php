<?php

namespace App\Console\Commands;

class MigrateTenantReset extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant:reset {--company-id=}';
    protected $description = 'Rollback all database migrations on provided tenant database(s)';

    public function handle() {
        $this->call('optimize:clear');
        $this->call('migrate:reset', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
        ]);
    }
}
