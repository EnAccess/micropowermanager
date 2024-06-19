<?php

namespace App\Console\Commands;

class Migrator extends AbstractSharedCommand
{
    protected $signature = 'migrator:migrate';
    protected $description = 'Run all migrations on provided company database';

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
