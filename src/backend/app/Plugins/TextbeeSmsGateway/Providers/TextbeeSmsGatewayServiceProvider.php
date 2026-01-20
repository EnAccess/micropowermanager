<?php

namespace App\Plugins\TextbeeSmsGateway\Providers;

use App\Plugins\TextbeeSmsGateway\Console\Commands\InstallPackage;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use Illuminate\Support\ServiceProvider;

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
