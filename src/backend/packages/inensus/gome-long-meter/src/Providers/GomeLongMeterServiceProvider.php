<?php

namespace Inensus\GomeLongMeter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\GomeLongMeter\Console\Commands\GomeLongMeterDataSynchronizer;
use Inensus\GomeLongMeter\Console\Commands\InstallPackage;
use Inensus\GomeLongMeter\Modules\Api\GomeLongMeterApi;

class GomeLongMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            // removed the following line since it is not needed
            // $this->commands([InstallPackage::class,GomeLongMeterDataSynchronizer::class]);
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
        $this->app->booted(function ($app) {
            // $app->make(Schedule::class)->command('gome-long-meter:dataSync')->withoutOverlapping(50)
            //  ->appendOutputTo(storage_path('logs/cron.log'));
        });
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/gome-long-meter.php', 'gome-long-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('GomeLongMeterApi', GomeLongMeterApi::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/gome-long-meter.php' => config_path('gome-long-meter.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/gome-long-meter'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_gome_long_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_gome_long_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_gome_long_tables.php')[0];
                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/create_gome_long_tables.php.stub')
                    );
                }

                return $filesystem->glob($path.'*_create_gome_long_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_gome_long_tables.php")
            ->first();
    }
}
