<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;

trait RefreshMultipleDatabases {
    use RefreshDatabase;

    protected function refreshInMemoryDatabase() {
        Artisan::call(
            'migrate:fresh',
            ['--database' => 'micro_power_manager', '--path' => '/database/migrations/']
        );
        Artisan::call(
            'migrate:fresh  ',
            ['--database' => 'tenant', '--path' => '/database/migrations/tenant']
        );
        app(Kernel::class)->setArtisan(null);
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function refreshTestDatabase() {
        if (!RefreshDatabaseState::$migrated) {
            Artisan::call(
                'migrate:fresh',
                ['--database' => 'micro_power_manager', '--path' => '/database/migrations/']
            );

            Artisan::call(
                'migrate:fresh',
                ['--database' => 'tenant', '--path' => '/database/migrations/tenant']
            );

            app(Kernel::class)->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
