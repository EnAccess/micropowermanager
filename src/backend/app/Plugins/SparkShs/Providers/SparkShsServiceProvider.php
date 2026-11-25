<?php

namespace App\Plugins\SparkShs\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\SparkShs\Console\Commands\InstallPackage;
use App\Plugins\SparkShs\Modules\Api\SparkShsApi;
use Illuminate\Support\ServiceProvider;

class SparkShsServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::SparkShs;
    }

    public function tokenIssuer(): string {
        return SparkShsApi::class;
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
