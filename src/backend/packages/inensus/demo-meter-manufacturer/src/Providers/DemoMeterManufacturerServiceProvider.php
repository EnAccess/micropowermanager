<?php

namespace Inensus\DemoMeterManufacturer\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\DemoMeterManufacturer\Console\Commands\InstallPackage;
use Inensus\DemoMeterManufacturer\DemoMeterManufacturerApi;

class DemoMeterManufacturerServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations();
            $this->commands([InstallPackage::class]);
        }
    }

    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../../config/demo-meter-manufacturer.php', 'demo-meter-manufacturer');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

        // Register demo manufacturer API
        $this->app->bind(DemoMeterManufacturerApi::class);
        $this->app->alias(DemoMeterManufacturerApi::class, 'DemoMeterManufacturerApi');
    }

    public function publishConfigFiles(): void {
        $this->publishes([
            __DIR__.'/../../config/demo-meter-manufacturer.php' => config_path('demo-meter-manufacturer.php'),
        ]);
    }

    public function publishVueFiles(): void {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/demo-meter-manufacturer'
            ),
        ], 'vue-components');
    }

    public function publishMigrations(): void {
        if (!class_exists('CreateDemoMeterTables')) {
            $timestamp = date('Y_m_d_His');
            $this->publishes([
                __DIR__.'/../../database/migrations/create_demo_meter_tables.php.stub' => database_path('migrations').'/tenant/'.$timestamp.'_create_demo_meter_tables.php',
            ], 'migrations');
        }
    }
}
