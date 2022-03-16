<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use Illuminate\Console\Command;

class MigrationMultiplexer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrator:copy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy elder created migrations to company database folders';

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
        $sourcePath = __DIR__ . '/../../../';
        CompanyDatabase::all()->each(function ($companyDatabase) use ($sourcePath) {


            //dd('cp -r ' . $sourcePath . 'database/migrations/micropowermanager/ ' . $sourcePath . 'database/migrations/' . $companyDatabase->database_name);
            info('copying migration files in ' . $sourcePath . 'database/migrations/' . $companyDatabase->database_name);
            shell_exec('cp -r ' . $sourcePath . 'database/migrations/micropowermanager/ ' . $sourcePath . 'database/migrations/' . $companyDatabase->database_name);
            info('migration files copied');

            info('sed applying to migration files in ' . $sourcePath . '/database/migrations/' . $companyDatabase->database_name);
            shell_exec(
                'for file in ' . $sourcePath . '/database/migrations/' . $companyDatabase->database_name . '/*  
            do 
            sed -i \'\' \'s/micropowermanager/\'' . $companyDatabase->database_name . '\'/g\' $file 
            done');
        });
        info('done');
        return 0;

    }
}
