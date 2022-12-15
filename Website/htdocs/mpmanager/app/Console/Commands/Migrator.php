<?php

namespace App\Console\Commands;

class Migrator extends AbstractSharedCommand
{
    protected $signature = 'migrator:migrate';
    protected $description = 'Migrates all base migrations to provided database name';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate', [
            '--force' => true,
            '--database' => 'shard',
            '--path' => '/database/migrations/micropowermanager',
        ]);
    }
}
