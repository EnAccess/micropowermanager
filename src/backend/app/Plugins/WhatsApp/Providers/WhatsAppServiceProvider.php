<?php

namespace App\Plugins\WhatsApp\Providers;

use App\Plugins\WhatsApp\Console\Commands\InstallPackage;
use App\Plugins\WhatsApp\Console\Commands\ReconcileDelivery;
use App\Plugins\WhatsApp\Console\Commands\SendSmsFallbacks;
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
            SendSmsFallbacks::class,
        ]);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('whatsapp:reconcile-delivery')->everyMinute()->withoutOverlapping()
                ->when(fn (): bool => (bool) config('whatsapp.webhooks_enabled') && app()->environment('local', 'development', 'testing'));
            // Unlike reconciliation this must run wherever webhooks are absent:
            // a silent provider is exactly the case fallback exists for.
            $schedule->command('whatsapp:send-sms-fallbacks')->everyMinute()->withoutOverlapping();
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->register(EventServiceProvider::class);
    }
}
