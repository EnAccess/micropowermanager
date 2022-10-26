<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;

class InitializeShardingCommand extends Command
{
    protected $signature = 'sharding:initialize';
    protected $description = 'Creates all necessity sharding tables and do the migrations';


    public function handle()
    {
        $pdo = new PDO('mysql:host=' . env('DB_HOST'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $pdo->exec('CREATE DATABASE IF NOT EXISTS micro_power_manager');

        $this->call('optimize:clear');
        $this->call('migrate', [
            '--database' => 'micro_power_manager',
            '--path' => '/database/migrations/base',
        ]);
    }
}
