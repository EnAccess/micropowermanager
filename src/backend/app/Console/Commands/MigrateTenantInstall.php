<?php

namespace App\Console\Commands;

class MigrateTenantInstall extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant:install {--company-id=}';
    protected $description = 'Create the migration repository on provided tenant database(s)';

    public function handle() {
        $this->call('optimize:clear');
        $this->call('migrate:install', [
            '--database' => 'tenant',
        ]);
    }
}
