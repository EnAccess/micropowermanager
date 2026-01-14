<?php

namespace Inensus\GomeLongMeter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\GomeLongMeter\Console\Commands\GomeLongMeterDataSynchronizer;
use Inensus\GomeLongMeter\Console\Commands\InstallPackage;
use Inensus\GomeLongMeter\Modules\Api\GomeLongMeterApi;

class GomeLongMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        // removed the following line since it is not needed
        // $this->commands([InstallPackage::class,GomeLongMeterDataSynchronizer::class]);
        $this->commands([InstallPackage::class]);
        $this->app->booted(function ($app) {
            // $app->make(Schedule::class)->command('gome-long-meter:dataSync')->withoutOverlapping(50)
            //  ->appendOutputTo(storage_path('logs/cron.log'));
        });
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(GomeLongMeterApi::class);
        $this->app->alias(GomeLongMeterApi::class, 'GomeLongMeterApi');
    }
}
