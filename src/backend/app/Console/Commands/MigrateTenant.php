<?php

namespace App\Console\Commands;

class MigrateTenant extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant {--company-id=}';
    protected $description = 'Run the database migrations on provided tenant database(s)';

    public function handle() {
        $this->call('optimize:clear');
        $this->call('migrate', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/micropowermanager',
        ]);
    }
}
