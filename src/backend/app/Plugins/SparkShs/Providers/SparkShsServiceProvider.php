<?php

namespace App\Plugins\SparkShs\Providers;

use App\Lib\IVendingPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\SparkShs\Console\Commands\InstallPackage;
use App\Plugins\SparkShs\Modules\Api\SparkShsApi;
use Illuminate\Support\ServiceProvider;

class SparkShsServiceProvider extends ServiceProvider implements IVendingPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::SPARK_SHS;
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
        $this->app->bind(SparkShsApi::class);
        $this->app->alias(SparkShsApi::class, 'SparkShsApi');
    }
}
