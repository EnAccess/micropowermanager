<?php

namespace App\Plugins\EcreeeETender\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\EcreeeETender\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class EcreeeETenderServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'ecreee_e_tender';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'ECREEE E-Tender';
    }

    public function description(): string {
        return 'This plugin integrates Ecreee e-tender integration to Micropowermanager.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        $this->app->booted(function () {});
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
