<?php

namespace Inensus\ChintMeter\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\ChintMeter\Console\Commands\InstallPackage;
use Inensus\ChintMeter\Modules\Api\ChintMeterApi;

class ChintMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../../config/chint-meter.php', 'chint-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(ChintMeterApi::class);
        $this->app->alias(ChintMeterApi::class, 'ChintMeterApi');
    }

    public function publishConfigFiles(): void {
        $this->publishes([
            __DIR__.'/../../config/chint-meter.php' => config_path('chint-meter.php'),
        ]);
    }

    public function publishVueFiles(): void {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/chint-meter'),
        ], 'vue-components');
    }

    public function publishMigrations(Filesystem $filesystem): void {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_chint_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_chint_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_chint_tables.php')[0];
                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/create_chint_tables.php.stub')
                    );
                }

                return $filesystem->glob($path.'*_create_chint_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_chint_tables.php")
            ->first();
    }
}
