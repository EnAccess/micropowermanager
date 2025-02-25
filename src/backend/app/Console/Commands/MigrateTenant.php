<?php

namespace App\Console\Commands;

class MigrateTenant extends AbstractSharedCommand {
    protected $signature = 'migrate-tenant {--company-id=} {--force}';
    protected $description = 'Run the database migrations on provided tenant database(s)';

    public function handle() {
        $this->call('optimize:clear');

        $options = [
            '--database' => 'tenant',
            '--path' => '/database/migrations/micropowermanager',
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $this->call('migrate', $options);
    }
}
