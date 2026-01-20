<?php

namespace App\Plugins\DalyBms\Providers;

use App\Plugins\DalyBms\Console\Commands\CheckPayments;
use App\Plugins\DalyBms\Console\Commands\InstallPackage;
use App\Plugins\DalyBms\Console\Commands\SyncBikes;
use App\Plugins\DalyBms\Modules\Api\DalyBmsApi;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class DalyBmsServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
            SyncBikes::class,
            CheckPayments::class,
        ]);

        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('daly-bms:sync-bikes')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
        });
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(DalyBmsApi::class);
        $this->app->alias(DalyBmsApi::class, 'DalyBmsApi');
    }
}
