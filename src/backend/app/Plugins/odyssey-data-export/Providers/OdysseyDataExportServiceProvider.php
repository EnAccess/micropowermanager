<?php

namespace Inensus\OdysseyDataExport\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\OdysseyDataExport\Console\Commands\InstallPackage;

class OdysseyDataExportServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
