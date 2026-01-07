<?php

namespace Inensus\BulkRegistration\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\BulkRegistration\Console\Commands\InstallPackage;

class BulkRegistrationServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/bulk-registration.php',
            'bulk-registration'
        );
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        /*     $this->app->bind(GeographicalLocationFinder::class,function($app){
                 return
             });*/
    }
}
