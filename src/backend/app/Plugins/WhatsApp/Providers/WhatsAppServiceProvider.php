<?php

namespace App\Plugins\WhatsApp\Providers;

use App\Plugins\WhatsApp\Console\Commands\InstallPackage;
use App\Plugins\WhatsApp\Console\Commands\ReconcileDelivery;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
            ReconcileDelivery::class,
        ]);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('whatsapp:reconcile-delivery')->everyMinute()->withoutOverlapping()
                ->when(fn (): bool => (bool) config('whatsapp.webhooks_enabled') && app()->environment('local', 'development', 'testing'));
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->register(EventServiceProvider::class);
    }
}
