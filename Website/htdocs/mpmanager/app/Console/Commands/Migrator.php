<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class Migrator extends AbstractSharedCommand
{
    protected $signature = 'migrator:migrate {database-name}';
    protected $description = 'Migrates all base migrations to provided database name';

    public function runInCompanyScope(): void
    {
        $dbName = $this->argument('database-name');
        if ($dbName == 'base') {
            $this->call('optimize:clear');
            $this->call('migrate', [
                '--database' => 'micro_power_manager',
                '--path' => '/database/migrations/' . $dbName,
            ]);
        } elseif('all') {
            foreach (Config::get('database.connections') as $key => $details) {
                $this->call('optimize:clear');
                if ($this->isUnNecessaryConnection($key)) {
                    continue;
                }
                $this->info('Running migration for "' . $key . '"');

                if ($this->isKeyMicroPowerManager($key)) {
                    continue;
                }

                $this->call('migrate', [
                    '--database' => $key,
                    '--path' => '/database/migrations/' . $key,
                ]);

            }
        }else{

            $this->call('optimize:clear');
            $this->call('migrate', [
                '--database' => $dbName,
                '--path' => '/database/migrations/' . $dbName,
            ]);
        }
    }

    private function isUnNecessaryConnection($name): bool
    {
        if ($name == 'sqlite' || $name == 'pgsql' || $name == 'sqlsrv') {
            return true;
        }
        return false;
    }

    private function isKeyMicroPowerManager($key): bool
    {
        if ($key == 'micro_power_manager') {
            $this->call('migrate', [
                '--database' => $key,
                '--path' => '/database/migrations/base',
            ]);
            return true;
        }
        return false;
    }


}
