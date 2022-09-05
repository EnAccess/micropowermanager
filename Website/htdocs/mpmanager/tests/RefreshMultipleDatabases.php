<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

trait RefreshMultipleDatabases
{
    use RefreshDatabase;

    private function setShardTestDb()
    {
        $databases = config('database.connections');
        $databases['micro_power_manager'] = $databases['testing'];
        $databases['shard'] =  $databases['testing_test_company_db'] ;
        config()->set('database.connections', $databases);

}
    protected function refreshInMemoryDatabase()
    {
        $this->setShardTestDb();

        Artisan::call('migrate:fresh',
            ['--database' => 'micro_power_manager', '--path' => '/database/migrations/base']);
        Artisan::call('migrate:fresh',
            ['--database' => 'shard', '--path' => '/database/migrations/micropowermanager']);

        app(Kernel::class)->setArtisan(null);
        $this->app[Kernel::class]->setArtisan(null);
    }
    protected function refreshTestDatabase()
    {
        $this->setShardTestDb();
        if (!RefreshDatabaseState::$migrated) {

           Artisan::call('migrate:fresh',
               ['--database' => 'micro_power_manager', '--path' => '/database/migrations/base']);

            Artisan::call('migrate:fresh',
                ['--database' => 'shard', '--path' => '/database/migrations/test_company_db']);

            app(Kernel::class)->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

}
