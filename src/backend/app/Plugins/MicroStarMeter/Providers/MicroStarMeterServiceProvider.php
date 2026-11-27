<?php

namespace App\Plugins\MicroStarMeter\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\MicroStarMeter\Console\Commands\InstallPackage;
use App\Plugins\MicroStarMeter\Modules\Api\MicroStarMeterApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class MicroStarMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'micro_star_meter';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'MicroStar Meter';
    }

    public function description(): string {
        return 'This plugin integrates MicroStar meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.';
    }

    public function usageType(): string {
        return 'mini-grid';
    }

    public function tokenIssuer(): string {
        return MicroStarMeterApi::class;
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
