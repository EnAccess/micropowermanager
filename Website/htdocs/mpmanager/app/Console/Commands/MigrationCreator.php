<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use Illuminate\Console\Command;

class MigrationCreator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:create {migration-name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'creates new migrations into micropowermanager direction.';

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
        $migrationName = $this->argument('migration-name');
        $this->call('make:migration', [
            'name' => $migrationName,
            '--path' => '/database/migrations/micropowermanager'
        ]);


        return 0;
    }
}
