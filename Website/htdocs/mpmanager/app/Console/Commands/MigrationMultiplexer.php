<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use Illuminate\Console\Command;

class MigrationMultiplexer extends AbstractSharedCommand
{
    protected $signature = 'migrator:copy';
    protected $description = 'Copy elder created migrations to company database folders';

    public function runInCompanyScope():void
    {
        $sourcePath = __DIR__ . '/../../../';
        CompanyDatabase::all()->each(function ($companyDatabase) use ($sourcePath) {
            $this->info('copying migration files in ' . $sourcePath . 'database/migrations/' .
                $companyDatabase->database_name);
            shell_exec('cp -r ' . $sourcePath . 'database/migrations/micropowermanager/* ' . $sourcePath .
                'database/migrations/' . $companyDatabase->database_name);
            $this->info('migration files copied');

            $this->info('sed applying to migration files in ' . $sourcePath . '/database/migrations/' .
                $companyDatabase->database_name);
            shell_exec(
                'for file in ' . $sourcePath . '/database/migrations/' . $companyDatabase->database_name . '/*.php
            do
              ##sed -i \'\' \'s/micropowermanager/\'' . $companyDatabase->database_name . '\'/g\' $file
              sed -i  \'s/micropowermanager/\'shard\'/g\' $file
            done');
        });
        $this->info('done');
    }
}
