<?php

namespace App\Plugins\TextbeeSmsGateway\Providers;

use App\Plugins\TextbeeSmsGateway\Console\Commands\FetchIncomingSms;
use App\Plugins\TextbeeSmsGateway\Console\Commands\InstallPackage;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class TextbeeSmsGatewayServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class, FetchIncomingSms::class]);
        $this->app->make(Schedule::class)->command('textbee-sms-gateway:fetch-incoming-sms')->everyTwoMinutes()
            ->appendOutputTo(storage_path('logs/cron.log'));
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(TextbeeSmsGateway::class);
        $this->app->alias(TextbeeSmsGateway::class, 'TextbeeSmsGateway');
    }
}
