<?php

namespace App\Console\Commands;

use Database\Seeders\ShardingDatabaseSeeder;
use Illuminate\Console\Command;

class InitializeShardingCommand extends Command
{
    protected $signature = 'sharding:initialize';
    protected $description = 'Creates all necessity sharding tables and do the migrations';

    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate', [
            '--database' => 'micro_power_manager',
            '--path' => '/database/migrations/base',
        ]);

        $this->call('db:seed', [
            '--class' => ShardingDatabaseSeeder::class,
        ]);
    }
}
