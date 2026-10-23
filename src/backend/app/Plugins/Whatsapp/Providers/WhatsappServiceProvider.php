<?php

namespace App\Plugins\Whatsapp\Providers;

use App\Plugins\Whatsapp\Console\Commands\InstallPackage;
use App\Plugins\Whatsapp\WhatsappGateway;
use Illuminate\Support\ServiceProvider;

class WhatsappServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(WhatsappGateway::class);
        $this->app->alias(WhatsappGateway::class, 'WhatsappGateway');
    }
}
