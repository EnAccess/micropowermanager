<?php

namespace App\Plugins\WhatsApp\Providers;

use App\Plugins\WhatsApp\Providers\RouteServiceProvider;
use App\Plugins\WhatsApp\Console\Commands\InstallPackage;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services
     */
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
        ]);
    }

    /**
     * Register any application services
     */
    public function register(): void {
        // Routes will be registered here
    }
}