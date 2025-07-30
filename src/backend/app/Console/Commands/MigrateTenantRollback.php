<?php

namespace App\Console\Commands;

class MigrateTenantRollback extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant:rollback {--company-id=}';
    protected $description = 'Rollback the last database migration on provided tenant database(s)';

    public function handle(): void {
        $this->call('optimize:clear');
        $this->call('migrate:rollback', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
        ]);
    }
}
