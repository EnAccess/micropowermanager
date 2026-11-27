<?php

namespace App\Plugins\OdysseyDataExport\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\OdysseyDataExport\Console\Commands\InstallPackage;
use Illuminate\Support\ServiceProvider;

class OdysseyDataExportServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'odyssey_data_export';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Odyssey Data Export';
    }

    public function description(): string {
        return 'This plugin developed for OdysseyDataExport functionality.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
