<?php

namespace Inensus\BulkRegistration\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\BulkRegistration\Console\Commands\InstallPackage;
use Inensus\BulkRegistration\Console\Commands\UpdatePackage;

class BulkRegistrationServiceProvider extends ServiceProvider {
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
        $this->mergeConfigFrom(
            __DIR__.'/../../config/bulk-registration.php',
            'bulk-registration'
        );
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        /*     $this->app->bind(GeographicalLocationFinder::class,function($app){
                 return
             });*/
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/bulk-registration.php' => config_path('bulk-registration.php'),
        ], 'configurations');
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/bulk-registration'
            ),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_bulk_registration_tables.php' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_bulk-registration_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_bulk-registration_tables.php')[0];
                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/create_bulk_registration_tables.php')
                    );
                }

                return $filesystem->glob($path.'*_create_bulk-registration_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_bulk-registration_tables.php")
            ->first();
    }
}
