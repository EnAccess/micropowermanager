<?php

namespace Inensus\AfricasTalking\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\AfricasTalking\AfricasTalkingGateway;
use Inensus\AfricasTalking\Console\Commands\InstallPackage;

class AfricasTalkingServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(AfricasTalkingGateway::class);
        $this->app->alias(AfricasTalkingGateway::class, 'AfricasTalkingGateway');
    }
}
