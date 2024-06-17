<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigratorCentralDatabase extends Command
{
    protected $signature = 'migrator:migrate_central_database';
    protected $description = 'Run all core migrations on the central `micro_power_manager` database';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate', [
            '--database' => 'micro_power_manager',
            '--path' => '/database/migrations/base',
        ]);
    }
}
