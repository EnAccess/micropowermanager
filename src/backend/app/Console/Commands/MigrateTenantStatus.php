<?php

namespace App\Console\Commands;

class MigrateTenantStatus extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant:status {--company-id=}';
    protected $description = 'Show the status of each migrations on provided tenant database(s)';

    public function handle(): void {
        $this->call('optimize:clear');
        $this->call('migrate:status', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
        ]);
    }
}
