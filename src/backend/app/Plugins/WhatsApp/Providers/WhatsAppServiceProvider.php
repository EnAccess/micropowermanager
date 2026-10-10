<?php

namespace App\Plugins\WhatsApp\Providers;

use App\Plugins\WhatsApp\Console\Commands\InstallPackage;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services
     */
    public function boot(): void {
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