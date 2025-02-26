<?php

namespace App\Console\Commands;

class MigrateTenantRefresh extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant:refresh {--company-id=}';
    protected $description = 'Reset and re-run all migrations on provided tenant database(s)';

    public function handle() {
        $this->call('optimize:clear');
        $this->call('migrate:refresh', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
        ]);
    }
}
