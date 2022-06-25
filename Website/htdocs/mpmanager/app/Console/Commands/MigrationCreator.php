<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use Illuminate\Console\Command;

class MigrationCreator extends AbstractSharedCommand
{
    protected $signature = 'migrator:create {migration-name}';
    protected $description = 'creates new migrations into micropowermanager direction.';

    public function runInCompanyScope(): void
    {
        $migrationName = $this->argument('migration-name');
        $this->call('make:migration', [
            'name' => $migrationName,
            '--path' => '/database/migrations/micropowermanager'
        ]);
    }
}
