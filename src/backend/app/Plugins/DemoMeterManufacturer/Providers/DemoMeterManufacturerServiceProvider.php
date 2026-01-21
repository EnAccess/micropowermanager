<?php

namespace App\Plugins\DemoMeterManufacturer\Providers;

use App\Plugins\DemoMeterManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoMeterManufacturer\DemoMeterManufacturerApi;
use Illuminate\Support\ServiceProvider;

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
