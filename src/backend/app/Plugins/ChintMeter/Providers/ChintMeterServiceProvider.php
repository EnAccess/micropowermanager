<?php

namespace App\Plugins\ChintMeter\Providers;

use App\Plugins\ChintMeter\Console\Commands\InstallPackage;
use App\Plugins\ChintMeter\Modules\Api\ChintMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
