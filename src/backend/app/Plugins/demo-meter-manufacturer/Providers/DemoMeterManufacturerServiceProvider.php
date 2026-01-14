<?php

namespace Inensus\DemoMeterManufacturer\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\DemoMeterManufacturer\Console\Commands\InstallPackage;
use Inensus\DemoMeterManufacturer\DemoMeterManufacturerApi;

class DemoMeterManufacturerServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

        // Register demo manufacturer API
        $this->app->bind(DemoMeterManufacturerApi::class);
        $this->app->alias(DemoMeterManufacturerApi::class, 'DemoMeterManufacturerApi');
    }
}
