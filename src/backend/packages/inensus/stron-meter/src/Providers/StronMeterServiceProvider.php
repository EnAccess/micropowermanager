<?php

namespace Inensus\StronMeter\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\StronMeter\Console\Commands\InstallPackage;
use Inensus\StronMeter\StronMeterApi;

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
