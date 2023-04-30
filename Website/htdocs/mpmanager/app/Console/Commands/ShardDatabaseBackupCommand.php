<?php

declare(strict_types=1);

namespace App\Console\Commands;

class ShardDatabaseBackupCommand extends AbstractSharedCommand
{
    protected $signature = 'shard:db.backup';

    public function handle()
    {

        $databaseName = config('database.connections.shard');
        $this->info("Starting backup sharding databases");
        dump($this->arguments());
        config(['database.connections.mysql' => $databaseName]);

        $this->call('backup:run', [
            '--only-db' => true,
            '--filename' => $databaseName['database'] . '_' . date('y-m-d H:i:s') . '.zip',
            '--disable-notifications' => true,
            '--db-name' => ['mysql']]);
    }
}
