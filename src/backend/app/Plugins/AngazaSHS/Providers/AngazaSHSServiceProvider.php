<?php

namespace App\Plugins\AngazaSHS\Providers;

use App\Plugins\AngazaSHS\Console\Commands\InstallPackage;
use App\Plugins\AngazaSHS\Modules\Api\AngazaSHSApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
