<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupFull extends Command {
    protected $signature = 'backup:full';
    protected $description = 'Run the backup for central database and all tenant databases';

    public function handle(): void {
        $this->info('Starting backup for central database');
        config(['database.connections.mysql' => config('database.connections.micro_power_manager')]);
        $this->call('backup:run', [
            '--only-db' => true,
            '--filename' => 'master_'.date('y-m-d H:i:s').'.zip',
            '--disable-notifications' => true,
            '--db-name' => ['mysql'],
        ]);
        $this->info('Completed backup for central database');
        $this->call('backup-tenant:run');
    }
}
