<?php

namespace App\Plugins\SparkShs\Providers;

use App\Plugins\SparkShs\Console\Commands\InstallPackage;
use Illuminate\Support\ServiceProvider;

class SparkShsServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
