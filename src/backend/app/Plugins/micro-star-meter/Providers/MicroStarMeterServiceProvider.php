<?php

namespace Inensus\MicroStarMeter\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\MicroStarMeter\Console\Commands\InstallPackage;
use Inensus\MicroStarMeter\Modules\Api\MicroStarMeterApi;

class MicroStarMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(MicroStarMeterApi::class);
        $this->app->alias(MicroStarMeterApi::class, 'MicroStarMeterApi');
    }
}
