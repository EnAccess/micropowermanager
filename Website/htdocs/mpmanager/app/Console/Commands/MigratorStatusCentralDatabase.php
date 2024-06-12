<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigratorStatusCentralDatabase extends Command
{
    protected $signature = 'migrator:migrate_status_central_database';
    protected $description = 'Show the status of all core migrations on the central `micro_power_manager` database';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate:status', [
            '--database' => 'micro_power_manager',
            '--path' => '/database/migrations/base',
        ]);
    }
}
