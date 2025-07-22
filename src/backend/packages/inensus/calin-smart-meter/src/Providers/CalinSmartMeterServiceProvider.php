<?php

namespace Inensus\CalinSmartMeter\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\CalinSmartMeter\CalinSmartMeterApi;
use Inensus\CalinSmartMeter\Console\Commands\InstallPackage;
use Inensus\CalinSmartMeter\Console\Commands\UpdatePackage;
use Inensus\CalinSmartMeter\Models\CalinSmartTransaction;

class CalinSmartMeterServiceProvider extends ServiceProvider {
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
        Relation::morphMap(
            [
                'calin_smart_transaction' => CalinSmartTransaction::class,
            ]
        );
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/calin-smart-meter.php', 'calin-smart-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('CalinSmartMeterApi', CalinSmartMeterApi::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/calin-smart-meter.php' => config_path('calin-smart-meter.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/calin-smart-meter'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_calin_smart_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_calin_smart_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_calin_smart_tables.php')[0];

                    file_put_contents($file, file_get_contents(__DIR__.'/../../database/migrations/create_calin_smart_tables.php.stub'));
                }

                return $filesystem->glob($path.'*_create_calin_smart_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_calin_smart_tables.php")
            ->first();
    }
}
