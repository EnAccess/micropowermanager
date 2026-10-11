<?php

namespace App\Plugins\WhatsappMessaging\Providers;

use App\Plugins\WhatsappMessaging\Console\Commands\InstallPackage;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use Illuminate\Support\ServiceProvider;

class WhatsappMessagingServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(WhatsappMessagingGateway::class);
        $this->app->alias(WhatsappMessagingGateway::class, 'WhatsappMessagingGateway');
    }
}
