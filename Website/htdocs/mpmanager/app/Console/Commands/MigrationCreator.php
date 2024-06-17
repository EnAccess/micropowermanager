<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrationCreator extends Command
{
    protected $signature = 'migrator:create {migration-name}';
    protected $description = 'Create new migration file for company databases';

    public function handle()
    {
        $migrationName = $this->argument('migration-name');
        $this->call('make:migration', [
            'name' => $migrationName,
            '--path' => '/database/migrations/micropowermanager',
        ]);

        return 0;
    }
}
