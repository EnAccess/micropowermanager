<?php

namespace App\Plugins\VodacomMobileMoney\Providers;

use App\Plugins\VodacomMobileMoney\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class VodacomMobileMoneyServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
