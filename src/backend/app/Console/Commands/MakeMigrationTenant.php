<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeMigrationTenant extends Command {
    protected $signature = 'make:migration-tenant {migration-name}';
    protected $description = 'Create new migration file for tenant database(s)';

    public function handle() {
        $migrationName = $this->argument('migration-name');
        $this->call('make:migration', [
            'name' => $migrationName,
            '--path' => '/database/migrations/tenant',
        ]);

        return 0;
    }
}
