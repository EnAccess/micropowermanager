<?php

declare(strict_types=1);

namespace App\Console\Commands;

class BackupTenantDatabase extends AbstractSharedCommand {
    protected $signature = 'backup-tenant:run';
    protected $description = 'Run the backup for provided tenant database(s)';

    public function handle() {
        $databaseName = config('database.connections.tenant');
        $this->info('Starting backup for tenant databases');
        dump($this->arguments());
        config(['database.connections.mysql' => $databaseName]);

        $this->call('backup:run', [
            '--only-db' => true,
            '--filename' => $databaseName['database'].'_'.date('y-m-d H:i:s').'.zip',
            '--disable-notifications' => true,
            '--db-name' => ['mysql']]);
        $this->info('Completed backup for tenant databases');
    }
}
