<?php

namespace Inensus\KelinMeter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\KelinMeter\Console\Commands\AccessTokenRefresher;
use Inensus\KelinMeter\Console\Commands\DataSynchronizer;
use Inensus\KelinMeter\Console\Commands\InstallPackage;
use Inensus\KelinMeter\Console\Commands\ReadDailyMeterConsumptions;
use Inensus\KelinMeter\Console\Commands\ReadMinutelyMeterConsumptions;
use Inensus\KelinMeter\Console\Commands\UpdatePackage;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\KelinMeterApi;
use Inensus\KelinMeter\Models\KelinCustomer;
use Inensus\KelinMeter\Models\KelinMeter;
use Inensus\KelinMeter\Models\KelinSyncSetting;
use Inensus\KelinMeter\Models\KelinTransaction;

class KelinMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
            ReadDailyMeterConsumptions::class,
            ReadMinutelyMeterConsumptions::class,
            AccessTokenRefresher::class,
            UpdatePackage::class,
            DataSynchronizer::class,
        ]);
        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('kelin-meter:access-token-refresher')->everyTenMinutes()->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
            $app->make(Schedule::class)->command('kelin-meter:dataSync')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
            $app->make(Schedule::class)->command('kelin-meter:read-minutely-consumptions')->everyFifteenMinutes()->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
            $app->make(Schedule::class)->command('kelin-meter:read-daily-consumptions')->dailyAt('00:30')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
        });
        Relation::morphMap(
            [
                'kelin_sync_setting' => KelinSyncSetting::class,
                'kelin_transaction' => KelinTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(KelinMeterApi::class, fn ($app): KelinMeterApi => new KelinMeterApi(
            $app->make(KelinMeter::class),
            $app->make(KelinCustomer::class),
            $app->make(KelinMeterApiClient::class),
        ));
        $this->app->alias('KelinMeterApi', KelinMeterApi::class);
    }
}
