<?php

namespace App\Plugins\DemoShsManufacturer\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\DemoShsManufacturer\Console\Commands\InstallPackage;
use App\Plugins\DemoShsManufacturer\DemoShsManufacturerApi;
use Illuminate\Support\ServiceProvider;

class DemoShsManufacturerServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::DemoShsManufacturer;
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
