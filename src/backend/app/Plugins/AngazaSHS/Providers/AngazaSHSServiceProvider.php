<?php

namespace App\Plugins\AngazaSHS\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\AngazaSHS\Console\Commands\InstallPackage;
use App\Plugins\AngazaSHS\Modules\Api\AngazaSHSApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AngazaSHSServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::AngazaShs;
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
