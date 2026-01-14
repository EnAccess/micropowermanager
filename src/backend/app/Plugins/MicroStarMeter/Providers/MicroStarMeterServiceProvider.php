<?php

namespace App\Plugins\MicroStarMeter\Providers;

use App\Plugins\MicroStarMeter\Console\Commands\InstallPackage;
use App\Plugins\MicroStarMeter\Modules\Api\MicroStarMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
