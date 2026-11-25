<?php

namespace App\Plugins\SunKingSHS\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\SunKingSHS\Console\Commands\InstallPackage;
use App\Plugins\SunKingSHS\Modules\Api\SunKingSHSApi;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class SunKingSHSServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::SunKingShs;
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
