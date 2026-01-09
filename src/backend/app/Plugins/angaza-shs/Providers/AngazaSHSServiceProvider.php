<?php

namespace Inensus\AngazaSHS\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\AngazaSHS\Console\Commands\InstallPackage;
use Inensus\AngazaSHS\Modules\Api\AngazaSHSApi;

class AngazaSHSServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(AngazaSHSApi::class);
        $this->app->alias(AngazaSHSApi::class, 'AngazaSHSApi');
    }
}
