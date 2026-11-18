<?php

namespace App\Plugins\DemoMeterManufacturer\Providers;

use App\Lib\IVendingPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\DemoMeterManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoMeterManufacturer\DemoMeterManufacturerApi;
use Illuminate\Support\ServiceProvider;

class DemoMeterManufacturerServiceProvider extends ServiceProvider implements IVendingPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::DEMO_METER_MANUFACTURER;
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

        // Register demo manufacturer API
        $this->app->bind(DemoMeterManufacturerApi::class);
        $this->app->alias(DemoMeterManufacturerApi::class, 'DemoMeterManufacturerApi');
    }
}
