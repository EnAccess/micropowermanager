<?php

namespace App\Plugins\Prospect\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\Prospect\Console\Commands\InstallPackage;
use App\Plugins\Prospect\Console\Commands\ProspectDataSynchronizer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ProspectServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'prospect';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Prospect';
    }

    public function description(): string {
        return 'This plugin developed for data integration with Prospect energy data platform for aggregation, validation and visualization.';
    }

    public function usageType(): string {
        return 'general';
    }

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
