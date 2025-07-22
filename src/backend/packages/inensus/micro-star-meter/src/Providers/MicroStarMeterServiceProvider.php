<?php

namespace Inensus\MicroStarMeter\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\MicroStarMeter\Console\Commands\InstallPackage;
use Inensus\MicroStarMeter\Console\Commands\UpdatePackage;
use Inensus\MicroStarMeter\Modules\Api\MicroStarMeterApi;

class MicroStarMeterServiceProvider extends ServiceProvider {
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
        $this->mergeConfigFrom(__DIR__.'/../../config/micro-star-meter.php', 'micro-star-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('MicroStarMeterApi', MicroStarMeterApi::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/micro-star-meter.php' => config_path('micro-star-meter.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/micro-star-meter'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_micro_star_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_micro_star_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_micro_star_tables.php')[0];
                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/create_micro_star_tables.php.stub')
                    );
                }

                return $filesystem->glob($path.'*_create_micro_star_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_micro_star_tables.php")
            ->first();
    }
}
