<?php

namespace Inensus\SunKingSHS\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\SunKingSHS\Console\Commands\InstallPackage;
use Inensus\SunKingSHS\Modules\Api\SunKingSHSApi;

class SunKingSHSServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(SunKingSHSApi::class);
        $this->app->alias(SunKingSHSApi::class, 'SunKingSHSApi');
    }
}
