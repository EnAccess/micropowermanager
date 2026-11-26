<?php

namespace App\Plugins\DemoMeterManufacturer\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\DemoMeterManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoMeterManufacturer\DemoMeterManufacturerApi;
use Illuminate\Support\ServiceProvider;

class DemoMeterManufacturerServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'demo_meter_manufacturer';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Demo Meter Manufacturer';
    }

    public function description(): string {
        return 'Demo manufacturer plugin for meters that generates realistic tokens and transactions without requiring real manufacturer API integration. Perfect for testing and demonstration purposes.';
    }

    public function usageType(): string {
        return 'general';
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
