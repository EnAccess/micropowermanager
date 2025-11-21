<?php

namespace Inensus\TextbeeSmsGateway\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\TextbeeSmsGateway\Console\Commands\InstallPackage;
use Inensus\TextbeeSmsGateway\TextbeeSmsGateway;

class TextbeeSmsGatewayServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(TextbeeSmsGateway::class);
        $this->app->alias(TextbeeSmsGateway::class, 'TextbeeSmsGateway');
    }
}
