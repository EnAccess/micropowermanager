<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigratorCentralDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:migrate_central_database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all core migrations on the central `micro_power_manager` database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->call('optimize:clear');
        $this->call('migrate', [
            '--database' => 'micro_power_manager',
            '--path' => '/database/migrations/base',
        ]);
    }
}
