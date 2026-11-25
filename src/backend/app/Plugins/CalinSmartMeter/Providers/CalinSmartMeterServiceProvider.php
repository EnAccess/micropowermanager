<?php

namespace App\Plugins\CalinSmartMeter\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\CalinSmartMeter\CalinSmartMeterApi;
use App\Plugins\CalinSmartMeter\Console\Commands\InstallPackage;
use App\Plugins\CalinSmartMeter\Models\CalinSmartTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CalinSmartMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::CalinSmartMeter;
    }

    public function tokenIssuer(): string {
        return CalinSmartMeterApi::class;
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                'calin_smart_transaction' => CalinSmartTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
