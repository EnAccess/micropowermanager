<?php

namespace App\Plugins\DemoShsManufacturer\Providers;

use App\Lib\IVendingPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\DemoShsManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoShsManufacturer\DemoShsManufacturerApi;
use Illuminate\Support\ServiceProvider;

class DemoShsManufacturerServiceProvider extends ServiceProvider implements IVendingPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::DEMO_SHS_MANUFACTURER;
    }

    public function tokenIssuer(): string {
        return DemoShsManufacturerApi::class;
    }

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
