<?php

namespace App\Console\Commands;

class Migrator extends AbstractSharedCommand
{
    protected $signature = 'migrator:migrate {database-name}';
    protected $description = 'Migrates all base migrations to provided database name';

    public function handle()
    {
        $dbName = $this->argument('database-name');
        $this->call('optimize:clear');
        $this->call('migrate', [
            '--database' => 'shard',
            '--path' => '/database/migrations/' . $dbName,
        ]);
    }
}
