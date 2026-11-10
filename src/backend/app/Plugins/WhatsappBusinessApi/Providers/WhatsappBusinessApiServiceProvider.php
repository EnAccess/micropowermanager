<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Providers;

use App\Plugins\WhatsappBusinessApi\Console\Commands\InstallPackage;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use Illuminate\Support\ServiceProvider;

class WhatsappBusinessApiServiceProvider extends ServiceProvider {
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
