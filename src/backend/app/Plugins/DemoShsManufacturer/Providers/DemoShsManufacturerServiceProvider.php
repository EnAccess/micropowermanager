<?php

namespace App\Plugins\DemoShsManufacturer\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\DemoShsManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoShsManufacturer\DemoShsManufacturerApi;
use Illuminate\Support\ServiceProvider;

class DemoShsManufacturerServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'demo_shs_manufacturer';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Demo SHS Manufacturer';
    }

    public function description(): string {
        return 'Demo manufacturer plugin for Solar Home Systems (SHS) that generates realistic tokens and transactions without requiring real manufacturer API integration. Perfect for testing and demonstration purposes.';
    }

    public function usageType(): string {
        return 'general';
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
    }
}
