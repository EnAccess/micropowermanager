<?php

namespace App\Plugins\SparkShs\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\SparkShs\Console\Commands\InstallPackage;
use App\Plugins\SparkShs\Modules\Api\SparkShsApi;
use Illuminate\Support\ServiceProvider;

class SparkShsServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'spark_shs';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Spark SHS';
    }

    public function description(): string {
        return 'This plugin adds SparkShs functionality to MicroPowerManager.';
    }

    public function usageType(): string {
        return 'general';
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
