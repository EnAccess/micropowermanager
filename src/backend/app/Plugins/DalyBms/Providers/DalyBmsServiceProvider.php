<?php

namespace App\Plugins\DalyBms\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\DalyBms\Console\Commands\CheckPayments;
use App\Plugins\DalyBms\Console\Commands\InstallPackage;
use App\Plugins\DalyBms\Console\Commands\SyncBikes;
use App\Plugins\DalyBms\Modules\Api\DalyBmsApi;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class DalyBmsServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'daly_bms';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Daly BMS';
    }

    public function description(): string {
        return 'This plugin developed for managing e-bikes with daly bms.';
    }

    public function usageType(): string {
        return 'e-bike';
    }

    public function tokenIssuer(): string {
        return DalyBmsApi::class;
    }

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
    }
}
