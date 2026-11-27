<?php

namespace App\Plugins\SunKingSHS\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\SunKingSHS\Console\Commands\InstallPackage;
use App\Plugins\SunKingSHS\Modules\Api\SunKingSHSApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class SunKingSHSServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'sun_king_shs';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'SunKing SHS';
    }

    public function description(): string {
        return 'This plugin integrates SunKing solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.';
    }

    public function usageType(): string {
        return 'shs';
    }

    public function tokenIssuer(): string {
        return SunKingSHSApi::class;
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
