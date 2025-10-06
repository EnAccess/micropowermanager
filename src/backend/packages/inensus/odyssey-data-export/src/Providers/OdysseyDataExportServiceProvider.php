<?php

namespace Inensus\OdysseyDataExport\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\OdysseyDataExport\Console\Commands\InstallPackage;
use Inensus\OdysseyDataExport\Providers\EventServiceProvider;
use Inensus\OdysseyDataExport\Providers\ObserverServiceProvider;
use Inensus\OdysseyDataExport\Providers\RouteServiceProvider;

class OdysseyDataExportServiceProvider extends ServiceProvider {
    public function boot() {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/odyssey-data-export.php', 'odyssey-data-export');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/odyssey-data-export.php' => config_path('odyssey-data-export.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/odyssey-data-export'
            ),
        ], 'vue-components');
    }
}
