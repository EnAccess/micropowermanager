<?php

namespace App\Plugins\GomeLongMeter\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\GomeLongMeter\Console\Commands\InstallPackage;
use App\Plugins\GomeLongMeter\Modules\Api\GomeLongMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class GomeLongMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'gome_long_meter';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'GomeLong Meter';
    }

    public function description(): string {
        return 'This plugin integrates GomeLong meters to Micropowermanager. It uses. user_id & user_password for creating tokens for energy.';
    }

    public function usageType(): string {
        return 'mini-grid';
    }

    public function tokenIssuer(): string {
        return GomeLongMeterApi::class;
    }

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
    }
}
