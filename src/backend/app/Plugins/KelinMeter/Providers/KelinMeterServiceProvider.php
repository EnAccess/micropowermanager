<?php

namespace App\Plugins\KelinMeter\Providers;

use App\Plugins\KelinMeter\Console\Commands\AccessTokenRefresher;
use App\Plugins\KelinMeter\Console\Commands\DataSynchronizer;
use App\Plugins\KelinMeter\Console\Commands\InstallPackage;
use App\Plugins\KelinMeter\Console\Commands\ReadDailyMeterConsumptions;
use App\Plugins\KelinMeter\Console\Commands\ReadMinutelyMeterConsumptions;
use App\Plugins\KelinMeter\Console\Commands\UpdatePackage;
use App\Plugins\KelinMeter\Http\Clients\KelinMeterApiClient;
use App\Plugins\KelinMeter\KelinMeterApi;
use App\Plugins\KelinMeter\Models\KelinCustomer;
use App\Plugins\KelinMeter\Models\KelinMeter;
use App\Plugins\KelinMeter\Models\KelinSyncSetting;
use App\Plugins\KelinMeter\Models\KelinTransaction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
