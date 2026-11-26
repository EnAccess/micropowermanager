<?php

namespace App\Plugins\StronMeter\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\StronMeter\Console\Commands\InstallPackage;
use App\Plugins\StronMeter\StronMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class StronMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'stron_meter';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Stron Meter';
    }

    public function description(): string {
        return 'This plugin integrates Stron meters to Micropowermanager. It uses the api login credentials for authentication.';
    }

    public function usageType(): string {
        return 'mini-grid';
    }

    public function tokenIssuer(): string {
        return StronMeterApi::class;
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
