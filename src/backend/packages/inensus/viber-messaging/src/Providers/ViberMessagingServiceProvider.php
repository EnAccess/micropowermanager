<?php

namespace Inensus\ViberMessaging\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\ViberMessaging\Console\Commands\InstallPackage;
use Inensus\ViberMessaging\ViberGateway;

class ViberMessagingServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(ViberGateway::class);
        $this->app->bind(ViberGateway::class, 'ViberGateway');
    }
}
