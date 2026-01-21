<?php

namespace App\Plugins\EcreeeETender\Providers;

use App\Plugins\EcreeeETender\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class EcreeeETenderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        $this->app->booted(function () {});
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
