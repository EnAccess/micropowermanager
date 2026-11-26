<?php

namespace App\Plugins\ChintMeter\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\ChintMeter\Console\Commands\InstallPackage;
use App\Plugins\ChintMeter\Modules\Api\ChintMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ChintMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'chint_meter';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Chint Meter';
    }

    public function description(): string {
        return 'This plugin integrates Chint meters to Micropowermanager. It uses username & password for creating tokens for energy.';
    }

    public function usageType(): string {
        return 'mini-grid';
    }

    public function tokenIssuer(): string {
        return ChintMeterApi::class;
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
