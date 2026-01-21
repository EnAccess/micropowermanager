<?php

namespace App\Plugins\AfricasTalking\Providers;

use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\AfricasTalking\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
