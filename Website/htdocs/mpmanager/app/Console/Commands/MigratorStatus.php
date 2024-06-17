<?php

namespace App\Console\Commands;

class MigratorStatus extends AbstractSharedCommand
{
    protected $signature = 'migrator:migrate_status';
    protected $description = 'Show the status of all migrations on provided company database';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate:status', [
            '--database' => 'shard',
            '--path' => '/database/migrations/micropowermanager',
        ]);
    }
}
