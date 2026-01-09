<?php

namespace Inensus\DemoShsManufacturer\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\DemoShsManufacturer\Console\Commands\InstallPackage;
use Inensus\DemoShsManufacturer\DemoShsManufacturerApi;

class DemoShsManufacturerServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

        // Register demo manufacturer API
        $this->app->bind(DemoShsManufacturerApi::class);
        $this->app->alias(DemoShsManufacturerApi::class, 'DemoShsManufacturerApi');
    }
}
