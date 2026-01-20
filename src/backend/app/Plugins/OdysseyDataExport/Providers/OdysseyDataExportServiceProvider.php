<?php

namespace App\Plugins\OdysseyDataExport\Providers;

use App\Plugins\OdysseyDataExport\Console\Commands\InstallPackage;
use Illuminate\Support\ServiceProvider;

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
