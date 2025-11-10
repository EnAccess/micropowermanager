<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\Prospect\Console\Commands\InstallPackage;
use Inensus\Prospect\Console\Commands\ProspectDataSynchronizer;

class ProspectServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class, ProspectDataSynchronizer::class]);
        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('prospect:dataSync')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
        });
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
