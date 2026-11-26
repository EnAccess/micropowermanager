<?php

namespace App\Plugins\AngazaSHS\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\AngazaSHS\Console\Commands\InstallPackage;
use App\Plugins\AngazaSHS\Modules\Api\AngazaSHSApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AngazaSHSServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'angaza_shs';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Angaza SHS';
    }

    public function description(): string {
        return 'This plugin integrates Angaza solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.';
    }

    public function usageType(): string {
        return 'shs';
    }

    public function tokenIssuer(): string {
        return AngazaSHSApi::class;
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
