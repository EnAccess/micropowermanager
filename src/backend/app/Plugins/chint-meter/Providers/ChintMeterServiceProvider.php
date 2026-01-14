<?php

namespace Inensus\ChintMeter\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\ChintMeter\Console\Commands\InstallPackage;
use Inensus\ChintMeter\Modules\Api\ChintMeterApi;

class ChintMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(ChintMeterApi::class);
        $this->app->alias(ChintMeterApi::class, 'ChintMeterApi');
    }
}
