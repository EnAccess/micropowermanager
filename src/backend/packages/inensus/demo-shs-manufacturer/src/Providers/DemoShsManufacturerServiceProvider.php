<?php

namespace Inensus\DemoShsManufacturer\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\DemoShsManufacturer\Console\Commands\InstallPackage;
use Inensus\DemoShsManufacturer\DemoShsManufacturerApi;
use Inensus\DemoShsManufacturer\Providers\EventServiceProvider;
use Inensus\DemoShsManufacturer\Providers\ObserverServiceProvider;
use Inensus\DemoShsManufacturer\Providers\RouteServiceProvider;

class DemoShsManufacturerServiceProvider extends ServiceProvider {
    public function boot() {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishMigrations();
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/demo-shs-manufacturer.php', 'demo-shs-manufacturer');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

        // Register demo manufacturer API
        $this->app->bind(DemoShsManufacturerApi::class);
        $this->app->alias(DemoShsManufacturerApi::class, 'DemoShsManufacturerApi');
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/demo-shs-manufacturer.php' => config_path('demo-shs-manufacturer.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/demo-shs-manufacturer'
            ),
        ], 'vue-components');
    }

    public function publishMigrations() {
        if (!class_exists('CreateDemoShsTables')) {
            $timestamp = date('Y_m_d_His');
            $this->publishes([
                __DIR__.'/../../database/migrations/create_demo_shs_tables.php.stub' => database_path('migrations').'/tenant/'.$timestamp.'_create_demo_shs_tables.php',
            ], 'migrations');
        }
    }
}
