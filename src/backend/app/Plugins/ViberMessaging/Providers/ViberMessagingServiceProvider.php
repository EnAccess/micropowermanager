<?php

namespace App\Plugins\ViberMessaging\Providers;

use App\Plugins\ViberMessaging\Console\Commands\InstallPackage;
use App\Plugins\ViberMessaging\ViberGateway;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
