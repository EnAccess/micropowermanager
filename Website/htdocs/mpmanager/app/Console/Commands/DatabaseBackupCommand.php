<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup';

    public function handle(): void
    {
        $this->info('Make copy of master db');
        config(['database.connections.mysql' => config('database.connections.micro_power_manager')]);
        $this->call('backup:run', [
            '--only-db' => true,
            '--filename' => 'master_' . date('y-m-d H:i:s') . '.zip',
            '--disable-notifications' => true,
            '--db-name' => ['mysql']
        ]);
        $this->info("Starting backup for sharding databases");
        $this->call('shard:db.backup');
    }
}
