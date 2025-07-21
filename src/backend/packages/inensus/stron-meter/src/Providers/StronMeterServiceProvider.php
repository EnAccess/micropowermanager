<?php

namespace Inensus\StronMeter\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\StronMeter\Console\Commands\InstallPackage;
use Inensus\StronMeter\Console\Commands\UpdatePackage;
use Inensus\StronMeter\StronMeterApi;

class StronMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class, UpdatePackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/stron-meter.php', 'stron-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('StronMeterApi', StronMeterApi::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/stron-meter.php' => config_path('stron-meter.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/stron-meter'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_stron_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_stron_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_stron_tables.php')[0];

                    file_put_contents($file, file_get_contents(__DIR__.'/../../database/migrations/create_stron_tables.php.stub'));
                }

                return $filesystem->glob($path.'*_create_stron_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_stron_tables.php")
            ->first();
    }
}
