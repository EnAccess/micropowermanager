<?php

namespace Inensus\OdysseyDataExport\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\OdysseyDataExport\Console\Commands\InstallPackage;

class OdysseyDataExportServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../../config/odyssey-data-export.php', 'odyssey-data-export');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }

    public function publishConfigFiles(): void {
        $this->publishes([
            __DIR__.'/../../config/odyssey-data-export.php' => config_path('odyssey-data-export.php'),
        ]);
    }

    public function publishVueFiles(): void {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/odyssey-data-export'
            ),
        ], 'vue-components');
    }
}
