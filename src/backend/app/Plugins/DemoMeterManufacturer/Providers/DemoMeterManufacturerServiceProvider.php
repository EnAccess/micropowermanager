<?php

namespace App\Plugins\DemoMeterManufacturer\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\DemoMeterManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoMeterManufacturer\DemoMeterManufacturerApi;
use Illuminate\Support\ServiceProvider;

class DemoMeterManufacturerServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::DemoMeterManufacturer;
    }

    public function tokenIssuer(): string {
        return DemoMeterManufacturerApi::class;
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
