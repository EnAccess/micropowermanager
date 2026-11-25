<?php

namespace App\Plugins\StronMeter\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\StronMeter\Console\Commands\InstallPackage;
use App\Plugins\StronMeter\StronMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class StronMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::StronMeter;
    }

    public function tokenIssuer(): string {
        return StronMeterApi::class;
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
