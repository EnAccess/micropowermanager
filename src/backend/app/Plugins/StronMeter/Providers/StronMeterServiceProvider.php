<?php

namespace App\Plugins\StronMeter\Providers;

use App\Plugins\StronMeter\Console\Commands\InstallPackage;
use App\Plugins\StronMeter\StronMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class StronMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(StronMeterApi::class);
        $this->app->alias(StronMeterApi::class, 'StronMeterApi');
    }
}
