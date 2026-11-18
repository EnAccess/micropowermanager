<?php

namespace App\Plugins\CalinSmartMeter\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\CalinSmartMeter\CalinSmartMeterApi;
use App\Plugins\CalinSmartMeter\Console\Commands\InstallPackage;
use App\Plugins\CalinSmartMeter\Models\CalinSmartTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CalinSmartMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::CALIN_SMART_METER;
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
